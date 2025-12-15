<?php
  /**
   * Proses form appointment:
   * 1. Simpan data ke database MySQL
   * 2. Kirim email menggunakan library "PHP Email Form" bawaan template
   */

  // ====== KONFIGURASI DATABASE (SESUAIKAN DENGAN PUNYA ANDA) ======
  $db_host = "localhost";      // biasanya: localhost
  $db_user = "root";           // user MySQL
  $db_pass = "";               // password MySQL
  $db_name = "simrs";        // nama database

  // Koneksi ke MySQL
  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

  if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
  }

  // Ambil data dari form (sesuai name di index.html)
  $name       = $_POST['name']       ?? '';
  $email      = $_POST['email']      ?? '';
  $phone      = $_POST['phone']      ?? '';
  $date       = $_POST['date']       ?? ''; // tipe input: datetime-local
  $department = $_POST['department'] ?? '';
  $doctor     = $_POST['doctor']     ?? '';
  $message    = $_POST['message']    ?? '';

  // Jika di database Anda pakai kolom DATETIME, value dari datetime-local bisa langsung dimasukkan
  // Contoh struktur tabel:
  // CREATE TABLE appointments (
  //   id INT AUTO_INCREMENT PRIMARY KEY,
  //   name VARCHAR(100),
  //   email VARCHAR(100),
  //   phone VARCHAR(20),
  //   appointment_datetime DATETIME,
  //   department VARCHAR(100),
  //   doctor VARCHAR(100),
  //   message TEXT,
  //   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  // );

  // ====== SIMPAN KE DATABASE ======
  $stmt = $conn->prepare("INSERT INTO appointments (name, email, phone, appointment_datetime, department, doctor, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt) {
    die("Prepare statement gagal: " . $conn->error);
  }
  $stmt->bind_param("sssssss", $name, $email, $phone, $date, $department, $doctor, $message);
  $saved_ok = $stmt->execute();
  $stmt->close();

  // ====== KONFIGURASI EMAIL ======
  // Ganti dengan email tujuan Anda
  $receiving_email_address = 'contact@example.com';

  if( file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php' )) {
    include( $php_email_form );
  } else {
    die( 'Unable to load the "PHP Email Form" Library!');
  }

  $contact = new PHP_Email_Form;
  $contact->ajax = true;

  $contact->to = $receiving_email_address;
  $contact->from_name  = $name;
  $contact->from_email = $email;
  $contact->subject    = 'Online Appointment Form';

  // Uncomment jika ingin pakai SMTP
  /*
  $contact->smtp = array(
    'host' => 'example.com',
    'username' => 'example',
    'password' => 'pass',
    'port' => '587'
  );
  */

  $contact->add_message( $name,       'Name');
  $contact->add_message( $email,      'Email');
  $contact->add_message( $phone,      'Phone');
  $contact->add_message( $date,       'Appointment Date/Time');
  $contact->add_message( $department, 'Department');
  $contact->add_message( $doctor,     'Doctor');
  $contact->add_message( $message,    'Message');

  $mail_ok = $contact->send();

  // ====== RESPONSE KE FRONTEND (validate.js EXPECT "OK" UNTUK SUKSES) ======
  if ($saved_ok && $mail_ok) {
    echo "OK";
  } else if (!$saved_ok) {
    echo "Gagal menyimpan ke database.";
  } else {
    echo "Gagal mengirim email.";
  }

  $conn->close();
?>
