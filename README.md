# ⚡ Generator Management System API  

### 💡 Description  
I developed this **backend system** using **Laravel (REST API)** to manage the accounting, reporting, and daily operations of **private electricity generators**.  
The system allows administrators and managers to handle **fuel imports, ampere sales, generator expenses, and loan repayments** in a clear and automated way.  

It supports multiple roles — from the **General Manager (Super Admin)** to **Department Admins** — each with specific access levels and permissions.

---

### 👥 Roles  
1️⃣ **Super Admin:**  
   - Has full control over all operations.  
   - Manages investments, users, and generator data.  

2️⃣ **Manager:**  
   - Oversees one or more generators.  
   - Records fuel imports, expenses, reports, and ampere sales.  

3️⃣ **Admin (Department):**  
   - Manages a single generator’s daily expenses.  
   - Tracks payments, food, and maintenance costs.  

---

### ⚙️ Tech Stack  
- **Backend Framework:** Laravel (REST API)  
- **Database:** MySQL  
- **Authentication:** Laravel Sanctum  
- **Tools:** XAMPP / Laragon / VS Code / Postman  

---

### 📦 Main Modules  
✅ Dashboard — overview of generators, usage, and income  
✅ Incentives — manage staff or partner bonuses  
✅ Generators — add, edit, or manage multiple generators  
✅ Expenses — handle generator and partner expenses  
✅ Ampere — record power usage, sales, and profits  
✅ Reports — generate detailed monthly reports  

---

### 🔁 Loan & Repayment System  
💰 The system includes a **loan management module** that:  
- Calculates each user’s total loan amount automatically  
- Tracks repayments with date and currency  
- Updates balance and repayment history in real time  
- Provides full reporting with signatures and timestamps  

---

### 🧠 How to Run  
```bash
# install dependencies
composer install

# copy and configure environment file
cp .env.example .env

# run migrations
php artisan migrate

# start the local development server
php artisan serve
