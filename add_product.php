<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "เพิ่มสินค้า";
include 'header.php';

// ดึงข้อมูลประเภทสินค้า
$sql = "SELECT * FROM type ORDER BY type_name";
$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header Section -->
            <div class="text-center mb-4">
                <h2 class="display-6 fw-bold text-primary mb-3">
                    <i class="fas fa-plus-circle me-3"></i>เพิ่มสินค้าใหม่
                </h2>
                <p class="lead text-muted">กรอกข้อมูลสินค้าและเลือกรูปภาพสำหรับแต่ละสี</p>
            </div>
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white py-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                <i class="fas fa-tshirt fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-1">จัดการสินค้า</h4>
                            <p class="mb-0 opacity-75">เลือกไซส์ สี และอัพโหลดรูปภาพ</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <form action="insert_product.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                        <div class="row g-4">
                            <!-- ไซส์สินค้าและราคา -->
                            <div class="col-12">
                                <div class="section-header mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="section-icon me-3">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-primary">ไซส์สินค้าและราคา</h5>
                                            <p class="text-muted mb-0">เลือกไซส์ที่ต้องการเพิ่มและกำหนดราคา</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <?php
                                    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];
                                    $colors = ['ขาว', 'ดำ', 'แดง', 'น้ำเงิน', 'เขียว', 'เหลือง', 'ส้ม', 'ม่วง', 'ชมพู', 'เทา', 'น้ำตาล', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'อื่นๆ'];
                                    foreach($sizes as $size):
                                        $size_lower = strtolower($size);
                                    ?>
                                    <div class="col-lg-4 col-md-6 col-sm-6">
                                        <div class="card size-card h-100" id="card-<?= $size_lower ?>">
                                            <div class="card-body text-center">
                                                <div class="size-label">
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox" name="sizes[]" 
                                                               value="<?= $size ?>" id="size-<?= $size_lower ?>" 
                                                               onchange="toggleSizeInput('<?= $size ?>')">
                                                        <label class="form-check-label fw-bold ms-2" for="size-<?= $size_lower ?>"><?= $size ?></label>
                                                    </div>
                                                </div>
                                                <div id="price-<?= $size_lower ?>" class="size-inputs" style="display: none;">
                                                    <!-- สีสำหรับไซส์นี้ -->
                                                    <div class="mb-2">
                                                        <label class="form-label small">สีสำหรับไซส์ <?= $size ?>:</label>
                                                        <div class="color-selection" id="colors-<?= $size_lower ?>">
                                                            <?php 
                                                            $color_codes = [
                                                                'ขาว' => '#FFFFFF',
                                                                'ดำ' => '#000000',
                                                                'แดง' => '#FF0000',
                                                                'น้ำเงิน' => '#0000FF',
                                                                'เขียว' => '#008000',
                                                                'เหลือง' => '#FFFF00',
                                                                'ส้ม' => '#FFA500',
                                                                'ม่วง' => '#800080',
                                                                'ชมพู' => '#FFC0CB',
                                                                'เทา' => '#808080',
                                                                'น้ำตาล' => '#A52A2A',
                                                                'ครีม' => '#F5F5DC',
                                                                'เบจ' => '#F5F5DC',
                                                                'ฟ้า' => '#87CEEB',
                                                                'เขียวอ่อน' => '#90EE90',
                                                                'อื่นๆ' => '#E0E0E0'
                                                            ];
                                                            foreach($colors as $color): 
                                                                $color_code = $color_codes[$color] ?? '#E0E0E0';
                                                                $text_color = in_array($color, ['ขาว', 'เหลือง', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'ชมพู']) ? '#000000' : '#FFFFFF';
                                                            ?>
                                                            <div class="form-check form-check-inline color-option">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="colors_<?= $size_lower ?>[]" 
                                                                       value="<?= $color ?>" 
                                                                       id="color_<?= $size_lower ?>_<?= $color ?>"
                                                                       data-color="<?= $color ?>"
                                                                       onchange="updateColorPreview('<?= $size_lower ?>', '<?= $color ?>')">
                                                                <label class="form-check-label color-label" 
                                                                       for="color_<?= $size_lower ?>_<?= $color ?>"
                                                                       style="background-color: <?= $color_code ?>; color: <?= $text_color ?>;"
                                                                       data-color-code="<?= $color_code ?>"
                                                                       data-text-color="<?= $text_color ?>">
                                                                    <?= $color ?>
                                                                </label>
                                                            </div>
                                                            <?php endforeach; ?>
                                </div>
                            </div>

                                                    <!-- รูปภาพสำหรับสีที่เลือก -->
                                                    <div class="mb-2" id="image-section-<?= $size_lower ?>" style="display: none;">
                                                        <label class="form-label small">รูปภาพสำหรับสีที่เลือก:</label>
                                                        <div class="color-images" id="color-images-<?= $size_lower ?>">
                                                            <!-- รูปภาพจะถูกเพิ่มที่นี่เมื่อเลือกสี -->
                                                        </div>
                            </div>

                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-success text-white">
                                                            <i class="fas fa-tag"></i>
                                                        </span>
                                                        <input type="number" name="price_<?= $size_lower ?>" class="form-control" 
                                                               placeholder="ราคา" min="0" step="0.01">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                            </div>
                            </div>
                                </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    เลือกไซส์ที่ต้องการเพิ่ม เลือกสีสำหรับแต่ละไซส์ และกำหนดราคาและจำนวน
                                </div>
                            </div>

                            <!-- ข้อมูลสินค้า -->
                            <div class="col-12">
                                <div class="section-header mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="section-icon me-3">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-primary">ข้อมูลสินค้า</h5>
                                            <p class="text-muted mb-0">กรอกข้อมูลพื้นฐานของสินค้า</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-4">
                                    <!-- ประเภทสินค้า -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="type_id" class="form-select" id="typeSelect" required>
                                                <option value="">เลือกประเภทสินค้า</option>
                                                <?php 
                                                mysqli_data_seek($result, 0);
                                                while($row = mysqli_fetch_assoc($result)): 
                                                ?>
                                                    <option value="<?= $row['type_id'] ?>">
                                                        <?= htmlspecialchars($row['type_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <label for="typeSelect">
                                                <i class="fas fa-tags me-2"></i>ประเภทสินค้า
                                            </label>
                                        </div>
                                    </div>

                                    <!-- คำอธิบายสั้น -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <textarea name="description" class="form-control" 
                                                      id="descriptionTextarea" rows="3" maxlength="255"
                                                      placeholder="คำอธิบายสั้นๆ ที่จะแสดงในหน้ารายการสินค้า"></textarea>
                                            <label for="descriptionTextarea">
                                                <i class="fas fa-align-left me-2"></i>คำอธิบายสั้น
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>ไม่เกิน 255 ตัวอักษร
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <!-- ปุ่มบันทึก -->
                            <div class="col-12">
                                <div class="d-flex gap-3 justify-content-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>บันทึกข้อมูล
                                </button>
                                    <a href="sh_product_ad.php" class="btn btn-outline-secondary btn-lg px-5">
                                    <i class="fas fa-times me-2"></i>ยกเลิก
                                </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function updateColorPreview(size, color) {
    // ฟังก์ชันสำหรับอัพเดทการแสดงผลสี
    const checkbox = document.getElementById(`color_${size}_${color}`);
    const label = document.querySelector(`label[for="color_${size}_${color}"]`);
    
    if (checkbox && label) {
        if (checkbox.checked) {
            // เมื่อเลือกสี
            label.style.transform = 'scale(1.05)';
            label.style.boxShadow = '0 0 0 3px rgba(13, 110, 253, 0.25)';
            // เพิ่มส่วนอัพโหลดรูปภาพสำหรับสีนี้
            addColorImageUpload(size, color);
        } else {
            // เมื่อยกเลิกเลือกสี
            label.style.transform = 'scale(1)';
            label.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            // ลบส่วนอัพโหลดรูปภาพสำหรับสีนี้
            removeColorImageUpload(size, color);
        }
    }
    
    // อัพเดทจำนวนสีที่เลือก
    updateSelectedColorsCount(size);
    // อัพเดทการแสดงส่วนรูปภาพ
    updateImageSection(size);
}

function updateSelectedColorsCount(size) {
    const sizeLower = size.toLowerCase();
    const selectedColors = document.querySelectorAll(`input[name="colors_${sizeLower}[]"]:checked`);
    const colorSelection = document.getElementById(`colors-${sizeLower}`);
    
    if (colorSelection && selectedColors.length > 0) {
        // แสดงจำนวนสีที่เลือก
        let countDisplay = colorSelection.querySelector('.color-count');
        if (!countDisplay) {
            countDisplay = document.createElement('div');
            countDisplay.className = 'color-count';
            countDisplay.style.cssText = 'font-size: 0.7rem; color: #0d6efd; font-weight: bold; margin-top: 5px; text-align: center;';
            colorSelection.appendChild(countDisplay);
        }
        countDisplay.textContent = `เลือกแล้ว ${selectedColors.length} สี`;
    } else {
        // ซ่อนจำนวนสีที่เลือก
        const countDisplay = colorSelection.querySelector('.color-count');
        if (countDisplay) {
            countDisplay.remove();
        }
    }
}

function addColorImageUpload(size, color) {
    const sizeLower = size.toLowerCase();
    const colorImagesContainer = document.getElementById(`color-images-${sizeLower}`);
    
    // ตรวจสอบว่ามีรูปภาพสำหรับสีนี้อยู่แล้วหรือไม่
    const existingImage = colorImagesContainer.querySelector(`[data-color="${color}"]`);
    if (existingImage) {
        return; // มีอยู่แล้ว ไม่ต้องเพิ่ม
    }
    
    // สร้าง div สำหรับรูปภาพของสีนี้
    const colorImageDiv = document.createElement('div');
    colorImageDiv.className = 'color-image-item mb-2';
    colorImageDiv.setAttribute('data-color', color);
    colorImageDiv.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <span class="badge me-2" style="background-color: ${getColorCode(color)}; color: ${getTextColor(color)};">
                ${color}
            </span>
            <small class="text-muted">จัดการสี ${color}</small>
        </div>
        
        <!-- จำนวนสินค้า -->
        <div class="mb-2">
            <label class="form-label small fw-bold text-primary">
                <i class="fas fa-hashtag me-1"></i>จำนวนสินค้า
            </label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-success text-white">
                    <i class="fas fa-boxes"></i>
                </span>
                <input type="number" 
                       name="color_amount_${sizeLower}_${color}" 
                       class="form-control" 
                       placeholder="จำนวนสินค้า" 
                       min="0" 
                       required>
                <span class="input-group-text">ชิ้น</span>
            </div>
        </div>
        
        <!-- รูปภาพสินค้า -->
        <div class="mb-2">
            <label class="form-label small fw-bold text-primary">
                <i class="fas fa-image me-1"></i>รูปภาพสินค้า
            </label>
            <div class="row g-2">
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-upload"></i>
                        </span>
                        <input type="file" 
                               name="color_images_${sizeLower}_${color}" 
                               class="form-control" 
                               accept="image/*"
                               onchange="previewColorImage(this, '${sizeLower}', '${color}')">
                    </div>
                    <small class="text-muted">อัพโหลดไฟล์รูปภาพ (JPG, PNG, WEBP)</small>
                </div>
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-info text-white">
                            <i class="fas fa-link"></i>
                        </span>
                        <input type="url" 
                               name="color_images_url_${sizeLower}_${color}" 
                               class="form-control" 
                               placeholder="หรือใส่ URL รูปภาพ"
                               onchange="previewColorImageUrl(this, '${sizeLower}', '${color}')">
                    </div>
                    <small class="text-muted">หรือใส่ลิงก์รูปภาพ</small>
                </div>
            </div>
        </div>
        
        <!-- Preview รูปภาพ -->
        <div class="color-image-preview mt-2" id="preview-${sizeLower}-${color}" style="display: none;">
            <div class="text-center">
                <img src="" alt="ตัวอย่างรูปภาพ" class="img-thumbnail" style="max-height: 120px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div class="mt-2">
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>รูปภาพสำหรับสี ${color}
                    </small>
                </div>
            </div>
        </div>
    `;
    
    colorImagesContainer.appendChild(colorImageDiv);
    
    // ลบ required attribute จาก input ที่เพิ่งสร้าง
    const fileInput = colorImageDiv.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.removeAttribute('required');
    }
    
    const urlInput = colorImageDiv.querySelector('input[type="url"]');
    if (urlInput) {
        urlInput.removeAttribute('required');
    }
    
    const numberInput = colorImageDiv.querySelector('input[type="number"]');
    if (numberInput) {
        numberInput.removeAttribute('required');
    }
}

function removeColorImageUpload(size, color) {
    const sizeLower = size.toLowerCase();
    const colorImagesContainer = document.getElementById(`color-images-${sizeLower}`);
    const colorImageDiv = colorImagesContainer.querySelector(`[data-color="${color}"]`);
    
    if (colorImageDiv) {
        colorImageDiv.remove();
    }
}

function updateImageSection(size) {
    const sizeLower = size.toLowerCase();
    const selectedColors = document.querySelectorAll(`input[name="colors_${sizeLower}[]"]:checked`);
    const imageSection = document.getElementById(`image-section-${sizeLower}`);
    
    if (selectedColors.length > 0) {
        imageSection.style.display = 'block';
    } else {
        imageSection.style.display = 'none';
    }
}

function previewColorImage(input, size, color) {
    const preview = document.getElementById(`preview-${size}-${color}`);
    const previewImg = preview.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

function previewColorImageUrl(input, size, color) {
    const preview = document.getElementById(`preview-${size}-${color}`);
    const previewImg = preview.querySelector('img');
    
    if (input.value) {
        previewImg.src = input.value;
        preview.style.display = 'block';
        
        // ตรวจสอบว่ารูปโหลดสำเร็จหรือไม่
        previewImg.onerror = function() {
            alert('ไม่สามารถโหลดรูปภาพจาก URL นี้ได้');
            preview.style.display = 'none';
            input.value = '';
        };
    } else {
        preview.style.display = 'none';
    }
}

function getColorCode(colorName) {
    const colors = {
        'ขาว': '#FFFFFF',
        'ดำ': '#000000',
        'แดง': '#FF0000',
        'น้ำเงิน': '#0000FF',
        'เขียว': '#008000',
        'เหลือง': '#FFFF00',
        'ส้ม': '#FFA500',
        'ม่วง': '#800080',
        'ชมพู': '#FFC0CB',
        'เทา': '#808080',
        'น้ำตาล': '#A52A2A',
        'ครีม': '#F5F5DC',
        'เบจ': '#F5F5DC',
        'ฟ้า': '#87CEEB',
        'เขียวอ่อน': '#90EE90',
        'อื่นๆ': '#E0E0E0'
    };
    return colors[colorName] || '#E0E0E0';
}

function getTextColor(colorName) {
    const lightColors = ['ขาว', 'เหลือง', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'ชมพู'];
    return lightColors.includes(colorName) ? '#000000' : '#FFFFFF';
}

function toggleSizeInput(size) {
    const priceDiv = document.getElementById('price-' + size.toLowerCase());
    const checkbox = document.getElementById('size-' + size.toLowerCase());
    const card = document.getElementById('card-' + size.toLowerCase());
    
    if (checkbox.checked) {
        priceDiv.style.display = 'block';
        card.classList.add('border-primary', 'shadow-sm');
        // เพิ่ม required attribute เมื่อเลือก (เฉพาะ input ราคาเท่านั้น)
        const priceInput = priceDiv.querySelector('input[type="number"][name*="price_"]');
        if (priceInput) {
            priceInput.required = true;
        }
    } else {
        priceDiv.style.display = 'none';
        card.classList.remove('border-primary', 'shadow-sm');
        // ลบ required attribute และค่าเมื่อไม่เลือก
        priceDiv.querySelectorAll('input').forEach(input => {
            input.required = false;
            if (input.type !== 'checkbox') {
                input.value = '';
            }
        });
    }
}

function validateForm() {
    const checkedSizes = document.querySelectorAll('input[name="sizes[]"]:checked');
    
    if (checkedSizes.length === 0) {
        alert('กรุณาเลือกไซส์อย่างน้อย 1 ไซส์');
        return false;
    }
    
    // ตรวจสอบว่าสำหรับแต่ละไซส์ที่เลือก มีการกรอกราคาและจำนวน
    for (let size of checkedSizes) {
        const sizeValue = size.value.toLowerCase();
        const priceInput = document.querySelector(`input[name="price_${sizeValue}"]`);
        const amountInput = document.querySelector(`input[name="amount_${sizeValue}"]`);
        
        // ตรวจสอบว่ามีการเลือกสีสำหรับไซส์นี้หรือไม่
        const colorInputs = document.querySelectorAll(`input[name="colors_${sizeValue}[]"]:checked`);
        
        if (!priceInput || !amountInput) {
            console.error(`ไม่พบ input สำหรับไซส์ ${size.value}`);
            continue;
        }
        
        if (colorInputs.length === 0) {
            alert(`กรุณาเลือกสีอย่างน้อย 1 สีสำหรับไซส์ ${size.value}`);
            return false;
        }
        
        if (!priceInput.value) {
            alert(`กรุณากรอกราคาสำหรับไซส์ ${size.value}`);
            return false;
        }
        
        // ตรวจสอบว่าราคาเป็นตัวเลขที่ถูกต้อง
        if (parseFloat(priceInput.value) <= 0) {
            alert(`กรุณากรอกราคาที่มากกว่า 0 สำหรับไซส์ ${size.value}`);
            return false;
        }
        
        // ตรวจสอบจำนวนสีที่เลือก
        for (let colorInput of colorInputs) {
            const color = colorInput.value;
            const colorAmountInput = document.querySelector(`input[name="color_amount_${sizeValue}_${color}"]`);
            
            if (colorAmountInput) {
                const colorAmount = parseInt(colorAmountInput.value) || 0;
                if (colorAmount <= 0) {
                    alert(`กรุณากรอกจำนวนที่มากกว่า 0 สำหรับสี ${color} ในไซส์ ${size.value}`);
                    return false;
                }
            }
        }
    }
    
    // ลบ required attribute จาก checkbox ที่ซ่อนอยู่เพื่อหลีกเลี่ยง HTML5 validation error
    const allColorCheckboxes = document.querySelectorAll('input[name*="colors_"][name*="[]"]');
    allColorCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.removeAttribute('required');
        }
    });
    
    return true;
}

// ฟังก์ชันสำหรับจัดการ checkbox ที่ซ่อนอยู่
function initializeFormValidation() {
    // ลบ required attribute จาก checkbox ที่ซ่อนอยู่ทั้งหมด
    const allColorCheckboxes = document.querySelectorAll('input[name*="colors_"][name*="[]"]');
    allColorCheckboxes.forEach(checkbox => {
        checkbox.removeAttribute('required');
    });
    
    // เพิ่ม event listener สำหรับการเปลี่ยนแปลง checkbox
    allColorCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // ไม่ต้องทำอะไร เพียงแค่ให้ browser รู้ว่า checkbox นี้ไม่ required
            if (!this.checked) {
                this.removeAttribute('required');
            }
        });
    });
}

// เรียกใช้เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    initializeFormValidation();
});
</script>

<style>
/* Background and Layout */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

/* Header Styles */
.display-6 {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Card Styles */
.card {
    border-radius: 20px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.card-header {
    border-radius: 20px 20px 0 0 !important;
    border: none;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
}

/* Section Headers */
.section-header {
    position: relative;
}

.section-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

/* Form Floating */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #0d6efd;
}

.form-floating > .form-select ~ label {
    color: #0d6efd;
}

/* Size Cards */
.size-card {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
    cursor: pointer;
    height: 100%;
    min-height: 280px;
    width: 100%;
    border-radius: 15px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    position: relative;
    overflow: hidden;
}

.size-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.size-card.border-primary {
    border-color: #0d6efd !important;
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.2);
}

.size-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0d6efd, #0a58ca);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.size-card.border-primary::before {
    opacity: 1;
}

.size-inputs {
    transition: all 0.3s ease;
}

.input-group-text {
    font-size: 0.875rem;
}

.form-control-sm {
    font-size: 0.875rem;
}

.card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    padding: 1rem;
}

.size-label {
    margin-bottom: 1rem;
}

.size-inputs {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.input-group {
    margin-bottom: 0.75rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.input-group:last-child {
    margin-bottom: 0;
}

.input-group-sm .form-control {
    padding: 0.6rem 0.8rem;
    font-size: 0.9rem;
    border: none;
    background: rgba(255, 255, 255, 0.9);
}

.input-group-sm .input-group-text {
    padding: 0.6rem 0.8rem;
    font-size: 0.9rem;
    border: none;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: white;
    font-weight: 600;
}

.input-group-sm .form-control:focus {
    box-shadow: none;
    background: rgba(255, 255, 255, 1);
}

/* Button Styles */
.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    background: transparent;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

@media (max-width: 768px) {
    .col-lg-4, .col-md-6, .col-sm-6 {
        margin-bottom: 1rem;
    }
    
    .size-card {
        min-height: 220px;
    }
    
    .color-selection {
        max-height: 120px;
        padding: 10px;
    }
    
    .color-label {
        font-size: 0.75rem;
        padding: 4px 8px;
        min-width: 60px;
    }
}

.color-selection {
    max-height: 150px;
    overflow-y: auto;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 80px;
    transition: all 0.3s ease;
}

.color-selection:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.1);
}

.color-option {
    margin-bottom: 0 !important;
}

.color-label {
    font-size: 0.8rem;
    padding: 8px 16px;
    border-radius: 20px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
    font-weight: 600;
    min-width: 75px;
    text-align: center;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    position: relative;
    overflow: hidden;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
}

.color-label:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-color: #0d6efd;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
}

.color-label::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.color-label:hover::before {
    left: 100%;
}

.color-selection .form-check-input {
    display: none !important;
    position: absolute;
    left: -9999px;
    opacity: 0;
}

.color-selection .form-check-input:checked + .color-label {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
    transform: scale(1.05);
}

.color-selection .form-check-input:checked + .color-label::after {
    content: '✓';
    position: absolute;
    top: -2px;
    right: -2px;
    background-color: #0d6efd;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* เอฟเฟกต์สำหรับสีอ่อน */
.color-label[style*="#FFFFFF"],
.color-label[style*="#FFFF00"],
.color-label[style*="#F5F5DC"],
.color-label[style*="#87CEEB"],
.color-label[style*="#90EE90"],
.color-label[style*="#FFC0CB"] {
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    border-color: rgba(0,0,0,0.2);
}

/* เอฟเฟกต์สำหรับสีเข้ม */
.color-label[style*="#000000"],
.color-label[style*="#FF0000"],
.color-label[style*="#0000FF"],
.color-label[style*="#008000"],
.color-label[style*="#800080"],
.color-label[style*="#A52A2A"] {
    text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.3);
}

/* Animation สำหรับการเลือกสี */
@keyframes colorSelect {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1.05); }
}

.color-selection .form-check-input:checked + .color-label {
    animation: colorSelect 0.3s ease;
}

/* Responsive design */
@media (max-width: 768px) {
    .color-selection {
        max-height: 100px;
        padding: 8px;
    }
    
    .color-label {
        font-size: 0.7rem;
        padding: 3px 6px;
        min-width: 50px;
    }
}

/* CSS สำหรับรูปภาพสี */
.color-image-item {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.color-image-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.1);
}

.color-image-item .badge {
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.color-image-preview {
    text-align: center;
    margin-top: 10px;
}

.color-image-preview img {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.color-image-preview img:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.form-control-sm {
    font-size: 0.8rem;
    padding: 0.5rem 0.7rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control-sm:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.color-images {
    max-height: 350px;
    overflow-y: auto;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    min-height: 100px;
    transition: all 0.3s ease;
}

.color-images:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.1);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.section-header {
    animation: fadeInUp 0.6s ease-out;
}

.size-card {
    animation: fadeInUp 0.6s ease-out;
}

.size-card:nth-child(1) { animation-delay: 0.1s; }
.size-card:nth-child(2) { animation-delay: 0.2s; }
.size-card:nth-child(3) { animation-delay: 0.3s; }
.size-card:nth-child(4) { animation-delay: 0.4s; }
.size-card:nth-child(5) { animation-delay: 0.5s; }
.size-card:nth-child(6) { animation-delay: 0.6s; }
.size-card:nth-child(7) { animation-delay: 0.7s; }

.color-label {
    animation: fadeInUp 0.4s ease-out;
}

/* Loading States */
.btn-primary:active {
    animation: pulse 0.3s ease-in-out;
}

/* Scrollbar Styling */
.color-selection::-webkit-scrollbar,
.color-images::-webkit-scrollbar {
    width: 6px;
}

.color-selection::-webkit-scrollbar-track,
.color-images::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.color-selection::-webkit-scrollbar-thumb,
.color-images::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border-radius: 10px;
}

.color-selection::-webkit-scrollbar-thumb:hover,
.color-images::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
}

/* Focus States */
.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Success States */
.color-selection .form-check-input:checked + .color-label {
    animation: pulse 0.3s ease-in-out;
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .display-6 {
        font-size: 2rem;
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .btn-lg {
        padding: 0.6rem 1.5rem;
        font-size: 1rem;
    }
    
    .size-card {
        min-height: 250px;
    }
}
</style>

<?php include 'footer.php'; ?>
