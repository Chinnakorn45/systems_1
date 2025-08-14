# ระบบยืม-คืนครุภัณฑ์และอุปกรณ์

ระบบจัดการการยืม-คืนครุภัณฑ์และวัสดุสำนักงาน พัฒนาด้วย PHP, MySQL และ Docker

## คุณสมบัติ

- ✅ ระบบล็อกอินและจัดการผู้ใช้
- ✅ จัดการครุภัณฑ์และอุปกรณ์
- ✅ ระบบยืม-คืนครุภัณฑ์
- ✅ จัดการวัสดุสำนักงาน
- ✅ แดชบอร์ดแสดงสถิติ
- ✅ รองรับภาษาไทย
- ✅ ใช้งานง่ายด้วย Docker

## การติดตั้ง

### ข้อกำหนดเบื้องต้น

- Docker
- Docker Compose

### ขั้นตอนการติดตั้ง

1. **Clone โปรเจค**
   ```bash
   git clone <repository-url>
   cd project_wprk
   ```

2. **สร้างไฟล์ .env** (ถ้ายังไม่มี)
   ```bash
   # Environment variables for borrowing system
   MYSQL_ROOT_PASSWORD=root_password_123
   MYSQL_DATABASE=borrowing_db
   MYSQL_USER=borrowing_user
   MYSQL_PASSWORD=my_secure_password
   ```

3. **รัน Docker Compose**
   ```bash
   docker-compose up -d
   ```

4. **รอสักครู่** ให้ระบบเริ่มต้นเสร็จ (ประมาณ 30-60 วินาที)

5. **เข้าถึงระบบ**
   - เว็บแอป: http://localhost
   - phpMyAdmin: http://localhost:8080

## ข้อมูลทดสอบ

### ผู้ใช้ระบบ
- **Admin**: `admin` / `password123`
- **Staff 1**: `staff1` / `password123`
- **Staff 2**: `staff2` / `password123`

### ข้อมูลเริ่มต้น
ระบบจะสร้างข้อมูลตัวอย่างอัตโนมัติ:
- หมวดหมู่ครุภัณฑ์ (คอมพิวเตอร์, อุปกรณ์สำนักงาน, เครื่องมือ, โสตทัศนูปกรณ์)
- ครุภัณฑ์ตัวอย่าง (โน้ตบุ๊ก, โปรเจคเตอร์, เมาส์, ฯลฯ)
- วัสดุสำนักงาน (ปากกา, กระดาษ, แฟ้ม, เทปกาว)
- การยืมตัวอย่าง

## โครงสร้างโปรเจค

```
project_wprk/
├── docker/
│   └── apache/
│       └── Dockerfile          # PHP + Apache container
├── sql/
│   └── init.sql               # ฐานข้อมูลเริ่มต้น
├── src/
│   ├── config.php             # การเชื่อมต่อฐานข้อมูล
│   ├── index.php              # หน้าหลัก
│   ├── login.php              # หน้าเข้าสู่ระบบ
│   ├── dashboard.php          # แดชบอร์ด
│   └── logout.php             # ออกจากระบบ
├── docker-compose.yml         # Docker services
├── .env                       # Environment variables
└── README.md                  # คู่มือการใช้งาน
```

## การใช้งาน

### สำหรับผู้ดูแลระบบ (Admin)
- จัดการผู้ใช้ระบบ
- เพิ่ม/แก้ไข/ลบครุภัณฑ์
- ดูรายงานการใช้งาน
- จัดการหมวดหมู่

### สำหรับเจ้าหน้าที่ (Staff)
- ยืม-คืนครุภัณฑ์
- เบิกจ่ายวัสดุสำนักงาน
- ดูประวัติการใช้งาน

## การพัฒนา

### เพิ่มหน้าใหม่
1. สร้างไฟล์ PHP ในโฟลเดอร์ `src/`
2. เพิ่มลิงก์ในเมนู sidebar ของ `dashboard.php`
3. ตรวจสอบสิทธิ์การเข้าถึงตาม role

### แก้ไขฐานข้อมูล
1. แก้ไขไฟล์ `sql/init.sql`
2. ลบ volume ฐานข้อมูล: `docker-compose down -v`
3. รันใหม่: `docker-compose up -d`

## การแก้ไขปัญหา

### ระบบไม่สามารถเชื่อมต่อฐานข้อมูลได้
```bash
# ตรวจสอบสถานะ containers
docker-compose ps

# ดู logs ของ MySQL
docker-compose logs db

# รีสตาร์ท MySQL
docker-compose restart db
```

### หน้าเว็บแสดงข้อผิดพลาด
```bash
# ดู logs ของ web container
docker-compose logs web

# รีสตาร์ท web container
docker-compose restart web
```

### ลบข้อมูลทั้งหมดและเริ่มใหม่
```bash
# หยุดและลบ containers พร้อม volumes
docker-compose down -v

# รันใหม่
docker-compose up -d
```

## การปรับแต่ง

### เปลี่ยนพอร์ต
แก้ไขไฟล์ `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # เปลี่ยนจาก 80 เป็น 8080
```

### เพิ่ม PHP Extensions
แก้ไขไฟล์ `docker/apache/Dockerfile`:
```dockerfile
RUN docker-php-ext-install mysqli mbstring pdo_mysql gd
```

## License

MIT License

## การสนับสนุน

หากพบปัญหาหรือต้องการความช่วยเหลือ กรุณาสร้าง Issue ใน repository 