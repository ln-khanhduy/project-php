</div> <!-- Close container -->

<!-- Footer -->
<footer class="bg-dark text-light mt-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="fas fa-mobile-alt"></i> PhoneStore</h5>
                <p>Chuyên cung cấp các sản phẩm điện thoại chính hãng với giá tốt nhất thị trường.</p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Liên kết nhanh</h6>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-light">Trang chủ</a></li>
                    <li><a href="products.php" class="text-light">Sản phẩm</a></li>
                    <li><a href="about.php" class="text-light">Giới thiệu</a></li>
                    <li><a href="contact.php" class="text-light">Liên hệ</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6>Chính sách</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-light">Bảo hành</a></li>
                    <li><a href="#" class="text-light">Đổi trả</a></li>
                    <li><a href="#" class="text-light">Vận chuyển</a></li>
                    <li><a href="#" class="text-light">Thanh toán</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6>Liên hệ</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Đường ABC, Quận 1, TP.HCM</li>
                    <li><i class="fas fa-phone me-2"></i> 1800-1234</li>
                    <li><i class="fas fa-envelope me-2"></i> info@phonestore.com</li>
                </ul>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p>&copy; 2024 PhoneStore. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>
</body>
</html>