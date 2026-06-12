# Gambaran proses bisnis service saya :

Client akan mengirimkan request POST data karyawan ke endpoint /api/v1/employees pada service saya. Data yang dikirimkan berisi informasi karyawan seperti nama, email, jabatan, dan data yang lain. Service akan melakukan validasi data terlebih dahulu sebelum menyimpan data karyawan ke database. Jika data valid, maka sistem akan membuat data karyawan baru pada tabel employees. Setelah data berhasil disimpan, sistem akan melakukan proses integrasi ke layanan eksternal sesuai kebutuhan Tugas 3 Integrasi Aplikasi Enterprise, SOAP dan RabbitMQ. Data yang berhasil diproses akan dikembalikan ke client dalam bentuk respon sukses.

# Alasan kenapa endpoint POST /api/v1/employees dimasukan ke SOAP :

Karena dalam proses bisnis pengelolaan data karyawan, pembuatan data karyawan baru merupakan transaksi penting yang harus memiliki jejak audit yang valid dan dapat dipertanggungjawabkan. Dengan mengirimkan log transaksi ke layanan SOAP Audit milik dosen, setiap aktivitas pembuatan data karyawan akan tercatat secara terpusat.

Saat proses audit berhasil dilakukan, service saya akan menerima receipt_number dari layanan SOAP sebagai bukti bahwa transaksi telah tercatat secara resmi pada sistem audit pusat. Receipt number tersebut kemudian disimpan pada tabel "audit_logs".

# Alasan kenapa endpoint POST /api/v1/employees disiarkan ke RabbitMQ :

Karena pada konsep Enterprise Application Integration, perubahan data pada suatu service sebaiknya dapat diketahui oleh service lain secara real-time tanpa harus melakukan polling atau request berulang ke API sumber.

Ketika data karyawan berhasil dibuat, service saya akan mengirimkan pesan (publish message) ke RabbitMQ Broker pusat menggunakan event seperti employee.created . Pesan tersebut berisi informasi penting terkait data karyawan yang baru dibuat.

Dengan mekanisme publish-subscribe ini, service lain yang membutuhkan informasi data karyawan baru dapat langsung menerima notifikasi secara real-time. Pendekatan ini meningkatkan loose coupling antar service, mengurangi ketergantungan langsung antar aplikasi, serta mendukung arsitektur event-driven yang umum digunakan pada sistem enterprise.

# Alasan penggunaan Federated SSO :

Service saya menggunakan service Federated Single Sign-On (SSO) untuk memastikan bahwa hanya pengguna atau service yang telah terautentikasi melalui Identity Provider pusat yang dapat mengakses endpoint yang dilindungi.
Melalui mekanisme SSO, proses autentikasi tidak dilakukan secara terpisah pada setiap service. Sebaliknya, service saya akan memverifikasi token yang diterbitkan oleh sistem SSO pusat. Pendekatan ini meningkatkan keamanan, memudahkan pengelolaan identitas pengguna, dan mendukung integrasi antar aplikasi dalam ekosistem enterprise yang sama.
