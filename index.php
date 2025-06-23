<?php
session_start(); // Mulai sesi

// Periksa apakah pengguna sudah login
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
$role = $is_logged_in ? $_SESSION['role'] : ''; // Ambil role juga
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link rel="shortcut icon" href="images/favicon.png" type="">

  <title> SCC | Booking </title>

  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/css/nice-select.min.css" integrity="sha512-CruCP+TD3yXzlvvijET8wV5WxxEh5H8P4cmz0RFbKK6FlZ2sYl3AEsKlLPHbniXKSrDdFewhbmBK5skbdsASbQ==" crossorigin="anonymous" />
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <link href="css/style.css" rel="stylesheet" />
  <link href="css/responsive.css" rel="stylesheet" />

</head>

<body>

  <div class="hero_area">
    <div class="bg-box">
      <img src="images/hero-bg.jpg" width="90%">
    </div>
    <header class="header_section">
      <div class="container">
        <nav class="navbar navbar-expand-lg custom_nav-container ">
          <a class="navbar-brand" href="index.php">
            <span>
              <img src="images/scc.png" alt="">
            </span>
          </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  mx-auto ">
              <li class="nav-item active">
                <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="book.php">Booking</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="about.html">About</a>
              </li>
            </ul>
            <div class="user_option">
               <?php if ($is_logged_in) : ?>
                    <span class="text-white mr-3">Welcome, <?php echo $username; ?>! (<?php echo $role; ?>)</span>
                    <a href="logout.php" class="order_online">Logout</a>
                    <?php if ($role == 'superadmin' || $role == 'admin') : ?>
                        <a href="admin/dashboard_admin.php" class="order_online ml-2">Admin Panel</a>
                    <?php endif; ?>
                <?php else : ?>
                    <a href="login.php" class="order_online">Login</a>
                    <a href="register.php" class="order_online" >Sign In</a>
                <?php endif; ?>
            </div>

          </div>
        </nav>
      </div>
    </header>
    <section class="slider_section ">
      <div id="customCarousel1" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="container ">
              <div class="row">
                <div class="col-md-7 col-lg-6 ">
                  <div class="detail-box">
                    <h1>
                      Stkip Convention Center
                    </h1>
                    <p>
                    Punya Rencana Acara Besar? Stkip Convention Center Aja!
                     Dari Pesta Nikahan Sampai Wisuda, Pokoknya Semua Acara Pentingmu Jadi Sempurna!                
                    </p>
                    <div class="btn-box">
                      <a href="info.html" class="btn1">
                        Booking Sekarang
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
    </section>
    </div>

  
  <br>
  <section class="food_section layout_padding-bottom">
    <div class="container">
      <div class="heading_container heading_center">
        <h2>
          Keunggulan
        </h2>
      </div>

      <div class="filters-content">
        <div class="row grid">
          <div class="col-sm-6 col-lg-4 all lapangan">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/o1.png" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                    Lokasi strategis
                  </h5>
                  <p>
                    Universitas PGRI Sumatera Barat Convention Center, Gn. Pangilun,
                    Kec. Padang Utara, Kota Padang, Sumatera Barat 25173
                  </p>
                  
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-4 all ">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/02.jpeg" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                    Kapasitas Besar
                  </h5>
                  <p>
                    Universitas PGRI Convention Centre (UPCC) mampu menampung hingga 3000 
                    dapat digunakan untuk berbagai macam acara seperti pernikahan, wisuda dan lainnya.
                  </p>
                  
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-4 all">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/harga.png" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                    Harga
                  </h5>
                  <p>
                    Universitas PGRI Convention Centre (UPCC) dengan Rp. 20 juta anda bisa memakai gedung ini satu hari full,jadi anda tidak perlu
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-4 all ">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/f4.jpg" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                   Area Parkir Luas
                  </h5>
                  <p>
                    STKIP Convention Center menyediakan tempat parkir dengan kapasitas yang cukup besar.Terdapat juga
                    basement sehingga mampu menampung banyak kendaraan
                  </p>
                  
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-4 all wc">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/f5.jpeg" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                    Sound sistem
                  </h5>
                  <p>
                  Memiliki Sound sistem bagus. Sehingga dapat membuat acara konsumen memiliki, 
                   suara yang jernih dan kuat di setiap sudut ruangan.
                  </p>
                  
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-4 all parkir">
            <div class="box">
              <div>
                <div class="img-box">
                  <img src="images/f6.png" alt="">
                </div>
                <div class="detail-box">
                  <h5>
                    Mushola
                  </h5>
                  <p>
                    Ruangan Mushalla untuk umat beragama muslim sehingga menambah kenyamanan pengunjung dalam beribadah ketika acara berlangsung
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="about_section layout_padding">
    <div class="container  ">

      <div class="row">
        <div class="col-md-6 ">
          <div class="img-box">
            <img src="images/scc.png" alt="">
          </div>
        </div>
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h2>
                Stkip Convention Center
              </h2>
            </div>
            <p>
              STKIP Convention Center adalah pilihan sempurna untuk acara Anda, menawarkan ruangan serbaguna dan 
              fasilitas lengkap seperti sound system berstandar konser, tata cahaya modern, serta area parkir luas. 
              Dengan tim profesional dan harga kompetitif, kami siap mewujudkan setiap momen tak terlupakan, dari seminar,wisuda dan pernikahan.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="col-md-6">
          <div class="map_container ">
            <div id="googleMap"></div>
          </div>
        </div>

  <section class="client_section layout_padding-bottom">
    <div class="container">
      <div class="heading_container heading_center psudo_white_primary mb_45">
        <h2>
         Komentar dari pelanggan
        </h2>
      </div>
      <div class="carousel-wrap row ">
        <div class="owl-carousel client_owl-carousel">
          <div class="item">
            <div class="box">
              <div class="detail-box">
                <p>
                  STKIP PGRI Convention Centre (SCC) merupakan gedung yang megah dan besar yang ada di kota Padang dan memiliki fasilitas dan ruangan yang memadai sesuai dengan kebutuhan pemerintahan kota padang.
                   Sehingga, SCC bisa dijadikan sebagai salah satu tempat pelaksanaan acara.
                </p>
                <h6>
                  H. Mahyeldi Ansharullah, SP 
                </h6>
                <p>
                 Gubernur Sumatra Barat
                </p>
              </div>
              <div class="img-box">
                <img src="images/client1.png" alt="" class="box-img">
              </div>
            </div>
          </div>
          <div class="item">
            <div class="box">
              <div class="detail-box">
                <p>
                    STKIP PGRI Convention Dengan area parkir yang luas dan gedung yang besar cocok untuk acara pernikahan,seminar dan perpisahan anak sekolah
                    Mudah mudahan tetap mempertahankan kualitas gedungnya.                      
                </p>
                <h6>
                  Putra
                </h6>
                <p>
                  Warga kota Padang
                </p>
              </div>
              <div class="img-box">
                <img src="images/client2.png" alt="" class="box-img">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer_section">
    <div class="container">
      <div class="row">
        <div class="col-md-4 footer-col">
          <div class="footer_contact">
            <h4>
              Contact Us
            </h4>
            <div class="contact_link_box">
              <a href="">
                <i class="fa fa-map-marker" aria-hidden="true"></i>
                <span>
                  Universitas PGRI Sumatera Barat Convention Center, Gn. Pangilun, Kec. Padang Utara, Kota Padang, Sumatera Barat 25173
                </span>
              </a>
              <a href="">
                <i class="fa fa-phone" aria-hidden="true"></i>
                <span>
                  Call +01 1234567890
                </span>
              </a>
              <a href="">
                <i class="fa fa-envelope" aria-hidden="true"></i>
                <span>
                  scc@gmail.com
                </span>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-4 footer-col">
          <div class="footer_detail">
            <a href="" class="footer-logo">
              SCC
            </a>
            <p>
              Pilihan yang tepat untuk Gedung Pesta Perkawinan, Seminar, Ujian, Wisuda dan Event Lainnya.
            </p>
            <div class="footer_social">
              <a href="">
                <i class="fa fa-facebook" aria-hidden="true"></i>
              </a>
              <a href="">
                <i class="fa fa-twitter" aria-hidden="true"></i>
              </a>
              <a href="">
                <i class="fa fa-linkedin" aria-hidden="true"></i>
              </a>
              <a href="">
                <i class="fa fa-instagram" aria-hidden="true"></i>
              </a>
              <a href="">
                <i class="fa fa-pinterest" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-4 footer-col">
          <h4>
            Opening Hours
          </h4>
          <p>
            Everyday
          </p>
          <p>
            10.00 Am -10.00 Pm
          </p>
        </div>
      </div>
      <div class="footer-info">
        <p>
          &copy; <span id="displayYear"></span> All Rights Reserved By
          &copy; <span id="displayYear"></span> Distributed By
          <a href="https://themewagon.com/" target="_blank">ThemeWagon</a>
        </p>
      </div>
    </div>
  </footer>
  <script src="js/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
  </script>
  <script src="js/bootstrap.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js">
  </script>
  <script src="https://unpkg.com/isotope-layout@3.0.4/dist/isotope.pkgd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/js/jquery.nice-select.min.js"></script>
  <script src="js/custom.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCh39n5U-4IoWpsVGUHWdqB6puEkhRLdmI&callback=myMap">
  </script>
  </body>

</html>