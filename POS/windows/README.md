# POSG Windows Installer

هذا المجلد يحتوي مشروع Installer لإنشاء ملف `EXE` لتثبيت النظام على Windows.

## المتطلبات
- Windows 10/11
- XAMPP مثبت مسبقًا
- Inno Setup 6

## بناء ملف EXE
1. ثبّت Inno Setup 6.
2. شغّل الملف:
   - `installer\windows\build_installer.bat`
3. الناتج سيكون:
   - `installer\windows\build\POSG_Installer.exe`

## ماذا يفعل الـ Installer
- ينسخ المشروع إلى:
  - `C:\xampp\htdocs\POSG` (أو مسار XAMPP الذي تختاره أثناء التثبيت)
- يستورد قاعدة البيانات من:
  - `database\full_install.sql`
- يحدث ملف:
  - `config\database.php`
  حسب إعدادات MySQL التي تدخلها في شاشة التثبيت.
- يطلب منك أيضًا منفذ Apache ليتم إنشاء رابط فتح النظام بشكل صحيح.

## ملاحظات
- يجب أن تكون خدمة MySQL شغالة أثناء التثبيت.
- يجب أن تكون خدمة Apache شغالة لفتح النظام بعد التثبيت.
- قاعدة البيانات المستخدمة: `posg`.
- بيانات الدخول الافتراضية بعد التثبيت:
  - `admin / admin123`
- للطباعة الصامتة (بدون اختيار طابعة/معاينة) استخدم:
  - ثبّت وشغّل `QZ Tray` على جهاز نقطة البيع.
  - `start_pos_kiosk_print.bat`
  - وإذا استمرت المعاينة بسبب جلسة متصفح مفتوحة استخدم:
    - `start_pos_kiosk_print_strict.bat`
  - اختصار `F1` يعتمد على `QZ Tray` للطباعة المباشرة.
  - واجعل `XP-80` (أو طابعتك) هي الطابعة الافتراضية في Windows.

## ملاحظة مهمة
- لا يمكن توليد ملف EXE من macOS مباشرة بدون بيئة Windows.
- أنشئ ملف EXE على جهاز Windows فقط.
