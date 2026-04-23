<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# 🔒 Laravel Race Condition Demo

مشروع لتوضيح مشكلة Race Condition في Laravel وكيفية حلها باستخدام Database Locking.

## 📋 المشكلة

عندما يحاول عدة users شراء منتج محدود الكمية بنفس الوقت، قد تحدث **Race Condition** تؤدي إلى:
- بيع منتج واحد عدة مرات (Overselling)
- stock سالب
- بيانات غير متسقة

## ✅ الحل

استخدام **Pessimistic Locking** مع **Optimistic Locking** لضمان:
- عدم حدوث overselling
- بيانات متسقة دائماً
- أمان في البيئات المتعددة المستخدمين

## 🚀 البدء السريع

### 1. تثبيت المتطلبات
```bash
composer install
php artisan migrate
```

### 2. اختبار مع Locking (آمن)
```bash
php artisan app:reset-database
php artisan app:test-single-product 6 4
# شغّل 6 workers في terminals منفصلة
php artisan app:check-results 4
```

### 3. اختبار بدون Locking (قد يظهر Race Condition)
```bash
php artisan app:reset-database
php artisan app:test-race-without-lock 6 4
# شغّل 6 workers في terminals منفصلة
php artisan app:check-results 4
```

## 📁 الملفات المهمة

```
app/
├── Jobs/
│   ├── ProcessOrderJob.php           # مع locking
│   └── ProcessOrderWithoutLockJob.php # بدون locking
└── Console/Commands/
    ├── ResetDatabase.php             # تنظيف البيانات
    ├── TestSingleProduct.php         # اختبار مع locking
    ├── TestRaceConditionWithoutLock.php # اختبار بدون locking
    └── CheckResults.php              # فحص النتائج

database/migrations/
└── 2025_01_04_000000_add_version_to_products_table.php # عمود version
```

## 📖 التفاصيل

اقرأ [`TESTING_GUIDE.md`](TESTING_GUIDE.md) للتعليمات التفصيلية.

## 🎯 الهدف التعليمي

هذا المشروع يوضح:
- كيف تحدث Race Condition
- تأثيرها على سلامة البيانات
- كيفية حلها باستخدام Database Locks
- أهمية الـ concurrency control

## 📊 النتائج المتوقعة

### مع Locking:
```
✅ SUCCESS: Results are correct!
Orders: 4, Stock: 0, Version: 4
```

### بدون Locking (قد تحدث Race Condition):
```
❌ ERROR: Results are incorrect!
💥 Overselling: 2 extra orders created
Orders: 6, Stock: -2
```

## 🔧 التقنيات المستخدمة

- **Laravel 10+**
- **MySQL/PostgreSQL**
- **Database Transactions**
- **Pessimistic Locking** (`lockForUpdate()`)
- **Optimistic Locking** (version column)
- **Queue System**

## 📚 المراجع

- [Laravel Database Locks](https://laravel.com/docs/queries#pessimistic-locking)
- [Database Concurrency Control](https://en.wikipedia.org/wiki/Concurrency_control)
- [Race Condition](https://en.wikipedia.org/wiki/Race_condition)

---

**استمتع بتعلم كيفية حماية التطبيقات من Race Conditions!** 🚀
