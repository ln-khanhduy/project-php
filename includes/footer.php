 </div> <!-- Close container -->

 <!-- Footer -->
 <style>
     body {
         min-height: 100vh;
         font-size: 0.95rem;
         display: flex;
         flex-direction: column;
     }

     footer.site-footer {
         width: 100%;
         padding-top: 1rem;
         padding-bottom: 1rem;
         margin-top: 2rem;
     }

     footer.site-footer .container {
         padding-top: 0.6rem;
         padding-bottom: 0.6rem;
     }

     footer.site-footer h6 {
         font-size: 0.85rem;
         letter-spacing: 0.05em;
     }
 </style>

 <footer class="bg-dark text-light site-footer">
     <div class="container py-4">
         <div class="row gy-3">
             <div class="col-md-4">
                 <strong class="d-block mb-2">PhoneStore</strong>
                 <p class="mb-1">Điện thoại chính hãng, bảo hành chính hãng, giao hàng toàn quốc.</p>
                 <p class="mb-0"><i class="fas fa-map-marker-alt me-1"></i> 123 Phường ABC, Quận 1, TP.HCM</p>
             </div>
             <div class="col-md-3">
                 <h6 class="text-uppercase">Liên kết</h6>
                 <ul class="list-unstyled mb-0">
                     <li><a href="index.php" class="text-light">Trang chủ</a></li>
                     <li><a href="public/login.php" class="text-light">Đăng nhập</a></li>
                     <li><a href="public/reset_password.php" class="text-light">Đổi mật khẩu</a></li>
                     <li><a href="public/google_oauth.php" class="text-light">Đăng nhập Gmail</a></li>
                 </ul>
             </div>
             <div class="col-md-3">
                 <h6 class="text-uppercase">Hỗ trợ</h6>
                 <ul class="list-unstyled mb-0">
                     <li><a href="tel:18001234" class="text-light">Hotline: 1800-1234</a></li>
                     <li><a href="mailto:info@phonestore.com" class="text-light">Email: info@phonestore.com</a></li>
                     <li><span class="text-light">8h-21h mọi ngày</span></li>
                 </ul>
             </div>
             <div class="col-md-2 text-center text-md-end">
                 <div class="small mb-2">Kết nối với chúng tôi</div>
                 <a href="#" class="text-light me-2"><i class="fab fa-facebook"></i></a>
                 <a href="#" class="text-light me-2"><i class="fab fa-instagram"></i></a>
                 <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
             </div>
         </div>
         <hr class="border-secondary my-3">
         <div class="text-center small mb-0">© <?= date('Y'); ?> PhoneStore. All rights reserved.</div>
     </div>
 </footer>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <script>
     var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
     var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
         return new bootstrap.Tooltip(tooltipTriggerEl)
     });
 </script>
</body>
</html>