<?php
header('Content-Type: application/json');

// โหลดข้อมูลจากไฟล์ JSON
function loadJsonData($filename) {
    $filepath = __DIR__ . '/' . $filename;
    if (file_exists($filepath)) {
        $jsonContent = file_get_contents($filepath);
        return json_decode($jsonContent, true);
    }
    return [];
}

// ปิดการ debug เพื่อความปลอดภัย
error_reporting(0);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_provinces':
        $provinces = loadJsonData('2provinces.php');
        $result = [];
        foreach ($provinces as $province) {
            $result[] = [
                'code' => $province['provinceCode'],
                'name' => $province['provinceNameTh'],
                'nameEn' => $province['provinceNameEn']
            ];
        }
        
        // เรียงลำดับตามชื่อจังหวัด
        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($result);
        break;
        
    case 'get_districts':
        $provinceName = $_GET['province'] ?? '';
        if (empty($provinceName)) {
            echo json_encode([]);
            break;
        }
        
        $geography = loadJsonData('1geography.php');
        $result = [];
        
        foreach ($geography as $item) {
            if ($item['provinceNameTh'] === $provinceName) {
                $result[] = [
                    'code' => $item['districtCode'],
                    'name' => $item['districtNameTh'],
                    'nameEn' => $item['districtNameEn']
                ];
            }
        }
        
        // ลบข้อมูลซ้ำ
        $unique_districts = [];
        $seen = [];
        foreach ($result as $district) {
            if (!in_array($district['name'], $seen)) {
                $unique_districts[] = $district;
                $seen[] = $district['name'];
            }
        }
        
        // เรียงลำดับตามชื่ออำเภอ
        usort($unique_districts, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($unique_districts);
        break;
        
    case 'get_subdistricts':
        $provinceName = $_GET['province'] ?? '';
        $districtName = $_GET['district'] ?? '';
        
        if (empty($provinceName) || empty($districtName)) {
            echo json_encode([]);
            break;
        }
        
        $geography = loadJsonData('1geography.php');
        $result = [];
        
        foreach ($geography as $item) {
            if ($item['provinceNameTh'] === $provinceName && 
                $item['districtNameTh'] === $districtName) {
                $result[] = [
                    'code' => $item['subdistrictCode'],
                    'name' => $item['subdistrictNameTh'],
                    'nameEn' => $item['subdistrictNameEn'],
                    'zipcode' => $item['postalCode']
                ];
            }
        }
        
        // ลบข้อมูลซ้ำ
        $unique_subdistricts = [];
        $seen = [];
        foreach ($result as $subdistrict) {
            if (!in_array($subdistrict['name'], $seen)) {
                $unique_subdistricts[] = $subdistrict;
                $seen[] = $subdistrict['name'];
            }
        }
        
        // เรียงลำดับตามชื่อตำบล
        usort($unique_subdistricts, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($unique_subdistricts);
        break;
        
    case 'get_zipcode':
        $provinceName = $_GET['province'] ?? '';
        $districtName = $_GET['district'] ?? '';
        $subdistrictName = $_GET['subdistrict'] ?? '';
        
        if (empty($provinceName) || empty($districtName) || empty($subdistrictName)) {
            echo json_encode('');
            break;
        }
        
        $geography = loadJsonData('1geography.php');
        
        $zipcode = '';
        foreach ($geography as $item) {
            if ($item['provinceNameTh'] === $provinceName && 
                $item['districtNameTh'] === $districtName &&
                $item['subdistrictNameTh'] === $subdistrictName) {
                $zipcode = $item['postalCode'];
                break;
            }
        }
        echo json_encode($zipcode);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
