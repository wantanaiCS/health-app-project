# 🥗 Web Application for Healthy Menu Recommendation & Planning
> **Academic Thesis Project** - เว็บไซต์วางแผนเมนูอาหารประจำสัปดาห์เพื่อสุขภาพ

โปรเจกต์เว็บแอปพลิเคชันเพื่อสุขภาพที่ออกแบบและพัฒนาขึ้นเพื่อช่วยให้ผู้ใช้งานสามารถวิเคราะห์ความต้องการทางโภชนาการ และวางแผนรายการอาหารประจำสัปดาห์ได้อย่างมีประสิทธิภาพ ตอบโจทย์ผู้ที่ต้องการดูแลสุขภาพและควบคุมอาหารอย่างเป็นระบบ

---

## 🚀 Key Features (ฟีเจอร์เด่นของระบบ)
* **User Dietary Analysis:** ระบบวิเคราะห์และคำนวณความต้องการทางโภชนาการเบื้องต้นตามเป้าหมายสุขภาพของผู้ใช้
* **Dynamic Weekly Meal Planner:** แดชบอร์ดสำหรับจัดตารางและวางแผนเมนูอาหารประจำสัปดาห์แบบโต้ตอบ (Interactive Dashboard)
* **Healthy Menu Recommendation:** ระบบแนะนำเมนูอาหารเพื่อสุขภาพที่เหมาะสมกับเงื่อนไขทางโภชนาการ
* **Async Data Management:** การดึงข้อมูลแบบอซิงโครนัส (Asynchronous Data Fetching) ช่วยให้การจัดการเมนูและอัปเดตสถานะทำได้อย่างรวดเร็วโดยไม่ต้องรีโหลดหน้าเว็บใหม่
* **Responsive Admin Panel:** มีระบบหลังบ้าน (Admin Management) สำหรับจัดการฐานข้อมูลเมนูอาหาร วัตถุดิบ และข้อมูลโภชนาการอย่างเป็นสัดส่วน

---

## 🛠️ Tech Stack & Architecture

| Layer | Technologies Used |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Responsive UI Layouts |
| **Backend** | PHP (Core Logic & Data Processing) |
| **Database Communication** | AJAX / Web APIs for dynamic data retrieval |
| **Version Control** | GitHub for team collaboration and integration |

---

## 📂 Project Structure (โครงสร้างโฟลเดอร์หลัก)
* `admin/` - ระบบจัดการหลังบ้านสำหรับผู้ดูแลระบบ (Admin Control Panel)
* `ajax/` - ไฟล์สำหรับจัดการ Request และส่งข้อมูลแบบ異期 (Asynchronous Operations)
* `api/` - ส่วนเชื่อมต่อข้อมูลหลังบ้านเพื่อรองรับการดึงข้อมูลแบบเรียลไทม์
* `assets/` - ไฟล์จัดเก็บ Media, Stylesheets และส่วนประกอบดีไซน์หน้าเว็บ
* `includes/` - ส่วนประกอบโครงสร้างเว็บที่ใช้ร่วมกัน (เช่น Header, Footer, Database Connection)
* `process/` - ไฟล์ประมวลผล Logic หลักของระบบ และการคำนวณแผนอาหาร

---
