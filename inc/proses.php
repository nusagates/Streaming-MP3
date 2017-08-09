<?php
require_once '../../../konfig.php';
global $user, $tabel;
$user = wp_get_current_user();
$id = $user->id;
$akses = $user->roles[0];
$tabel = "album";
$id_column = "album_id";
setlocale(LC_ALL, 'id_ID.UTF8');

function toAscii($str, $replace = array(), $delimiter = '-') {
  if (!empty($replace)) {
    $str = str_replace((array) $replace, ' ', $str);
  }

  $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
  $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
  $clean = strtolower(trim($clean, '-'));
  $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

  return $clean;
}

if (isset($_GET['ambil'])) {
  $menu = "";
  $ambil = $db->query("SELECT * from " . $tabel . "");
  while ($row = $ambil->fetchObject()) {
    $gambar = $row->album_image;
    $lihat = "";
    if (is_file('gambar/' . $gambar)) {
      $lihat .= "<a data-lihat='"
              . $gambar
              . "' title='Nama Vendor: "
              . $row->playlist_name
              . "' rel='gallery' href='data/gambar/"
              . $gambar
              . "' class='lihat-gambar btn btn-xs btn-primary'><i class='glyphicon glyphicon-eye-open'></i> </a> ";
    }
    $menu = "<a "
            . "data-edit='" . $row->$id_column . "' "
            . "class='tombol-edit btn btn-xs btn-success' href='#'><i class='fa fa-edit'></i></a> "
            . "<a data-hapus='" . $row->$id_column . "' class='tombol-hapus btn btn-xs btn-danger' href='#'><i class='fa fa-times'></i></a>";
    $status = $row->vendor_status === "1" ? "Aktif" : "Tidak Aktif";
    $data[] = array($row->album_name, $row->album_artist, $row->album_permalink, $row->album_source, $lihat . $menu);
  }
  echo json_encode($data);
}
if (isset($_GET['hapus'])) {
  $data = isset($_POST['data']) ? $_POST['data'] : '';
  $hapus = $db->prepare("delete from " . $tabel . " where " . $id_column . "=?");
  if ($hapus->execute(array($data))) {
    unlink('gambar/' . md5($data) . '.jpg');
    echo "1";
  }
}
if (isset($_GET['tambah'])) {

  if (pos_kosong("nama", $nama))
    exit("Kolom Nama Playlist wajib diisi!");
  if (pos_kosong("artis", $artis))
    exit("Artis wajib diisi!");
  if (pos_kosong("permalink", $permalink))
    exit("Permalink Wajib diisi!");
  if (pos_kosong("sumber", $sumber))
    exit("Sumber lagu wajib diisi!");
  if (pos_kosong("deskripsi", $deskripsi))
    exit("deskripsi wajib diisi!");
  pos_kosong("tag", $tag);
  pos_kosong("sameas", $sameas);
  $file = !empty($_FILES['file']) ? $_FILES['file'] : null;
  if (!$file || !$file['size'])
    exit('Pastikan scan surat telah dilampirkan');
  if ($file['error'])
    exit("Scan surat tidak bisa diunggah. Silahkan pilih yang lain!");
  if (!preg_match('~/jp(e|)g$~', $file['type']))
    exit("Formar gambar salah");
  if (!is_dir("gambar/"))
    exit("Folder Untuk Menyimpan Gambar Tidak Ada");
  $images_name = str_replace(" ", "-", $_FILES["file"]["name"]);
  try {
    $tambah = $db->prepare("insert into " . $tabel . "(album_name, album_sameas, album_artist, album_permalink, album_source, album_description, album_image) values(?,?,?,?,?,?,?)");
    if ($tambah->execute(array($nama, $sameas, $artis, $permalink, $sumber, $deskripsi, $images_name))) {
      $id_row = $db->lastInsertId();
      $json = file_get_contents($sumber);
      $data = json_decode($json);
      $created = $data->created;
      $d1 = $data->d1;
      $dir = $data->dir;
      $base = "https://" . $d1 . $dir;
      $identifier = preg_replace('/(?<!\ )[A-Z]/', ' $0', str_replace("-", " ", $data->metadata->identifier));

      for ($i = 0; $i < count($data->files); $i++) {
        if ($data->files[$i]->format === "VBR MP3") {
          $musik[] = array(
              "mp3" => "https://" . $d1 . $dir . "/" . $data->files[$i]->name,
              "title" => $data->files[$i]->name,
              "poster" => $gambar
          );
          $pil=$db->query("select track_permalink from track where track_permalink='".toAscii(str_replace("mp3", "", $data->files[$i]->name))."'");
          $total =$pil->rowCount();
          $track_permalink = toAscii(str_replace("mp3", "", $data->files[$i]->name));
          if($total>0){
            $track_permalink = toAscii(str_replace("mp3", "", $data->files[$i]->name))."_".$total;
          }
          $tambah = $db->prepare("insert into track(album_id, track_artist, track_name, track_permalink, track_source, track_tag, track_length) values(?,?,?,?,?,?,?)");
          if ($tambah->execute(array($id_row, $artis, str_replace(".mp3", "", $data->files[$i]->name), $track_permalink, $base . "/" . $data->files[$i]->name, $tag, $data->files[$i]->length))) {
            
          }
        }
      }
      if (!move_uploaded_file($_FILES["file"]["tmp_name"], 'gambar/' . $images_name)) {
        
      }
      echo "1";
    }
  } catch (PDOException $ex) {
    echo $ex->getMessage();
  }
}
if (isset($_GET['edit-form'])) {
  $id_data = $_GET['edit-form'];
  $ambil = $db->query("select * from " . $tabel . " where " . $id_column . "='$id_data'");
  $row = $ambil->fetchObject();
  ?>
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title">Edit</h4>
    </div>
    <div class="modal-body modal-edit">
      <div class="row">
        <form id="form-edit">
  <?php
  input_txt("nama", "Nama Playlist", "6", $row->playlist_name, "text", "placeholder='eg. An Nabawiyah Langitan'");
  input_txt("permalink", "Permalink", "6", $row->playlist_link, "text", "placeholder='eg. an-nabawiyah-langitan'");
  input_txt("kategori", "Kategori", "6", $row->playlist_kategori, "text", "placeholder='eg. sholawat'");
  input_txt("sumber", "Seumber Lagu", "6", $row->playlist_source, "text", "placeholder='eg. https://archive.org/metadata/SitiNurhaliza-FullAlbum/'");
  input_file();
  display_warning("warnings");
  ?>
          <input  type="hidden" value="<?php echo $row->$id_column ?>" name="id"/>
        </form>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-warning" data-dismiss="modal">Tutup</button>
      <button id="tombol-edit" type="button" class="btn btn-primary">Update</button>
    </div>
  </div>
  <?php
}
if (isset($_GET['edit'])) {
  if (pos_kosong("nama", $nama))
    exit("Kolom Nama Playlist wajib diisi!");
  if (pos_kosong("permalink", $permalink))
    exit("Permalink wajib diisi!");
  if (pos_kosong("kategori", $kategori))
    exit("Nama Kategori Wajib diisi!");
  if (pos_kosong("sumber", $sumber))
    exit("Seumber lagu wajib diisi!");

  function tambah() {
    global $db, $nama, $permalink, $kategori, $sumber;
    try {
      $update = $db->prepare("update " . $tabel . " set playlist_name=?, playlist_link=?, playlist_kategori=?, playlist_source=? where " . $id_column . "=?");
      if ($update->execute(array($nama, $permalink, $kategori, $sumber, $id_data))) {
        echo "1";
      }
    } catch (PDOException $ex) {
      echo $ex->getMessage();
    }
  }

  $file = !empty($_FILES['file']) ? $_FILES['file'] : null;
  if (!$file || !$file['size']) {
    tambah();
  } else {
    if ($file['error'])
      exit("Scan surat tidak bisa diunggah. Silahkan pilih yang lain!");
    if (!preg_match('~/jp(e|)g$~', $file['type']))
      exit("Formar gambar salah");
    if (!is_dir("gambar/"))
      exit("Folder Untuk Menyimpan Gambar Tidak Ada");
    tambah();
    if (!move_uploaded_file($_FILES["file"]["tmp_name"], 'gambar/' . md5($id_data) . '.jpg'))
      exit("Gagal Mengubah Gambar");
  }
}  