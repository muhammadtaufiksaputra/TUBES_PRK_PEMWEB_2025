
---

## 📊 PROGRESS OVERVIEW

| No | Fitur | Status | Priority | Complexity |
|----|-------|--------|----------|------------|
| 1 | ✅ Supplier Management | **DONE** | P0 | ⭐⭐ |
| 2 | ✅ Category Management | **DONE** | P0  | ⭐ |
| 3 | ⬜ Material Management | TODO | P0 | ⭐⭐⭐⭐ |
| 4 | ⬜ Material Images | TODO | P1 | ⭐⭐⭐ |
| 5 | ⬜ Stock In (Barang Masuk) | TODO | P0 | ⭐⭐⭐⭐ |
| 6 | ⬜ Stock Out (Barang Keluar) | TODO | P0 | ⭐⭐⭐⭐ |
| 7 | ⬜ Stock Adjustment | TODO | P1 | ⭐⭐⭐ |
| 8 | ⬜ Reports & Analytics | TODO | P1 | ⭐⭐⭐⭐ |
| 9 | ⬜ Activity Logs | TODO | P2 | ⭐⭐ |
| 10 | ⬜ User Management | TODO | P2 | ⭐⭐⭐ |
| 11 | ⬜ Role & Permission | TODO | P2 | ⭐⭐⭐ |


---

## 🎯 FITUR YANG SUDAH SELESAI

### ✅ 1. Supplier Management (COMPLETED)
**Files:**
- `src/models/Supplier.php` (18 methods)
- `src/controllers/api/SupplierApiController.php` (6 endpoints)
- `src/routes/api.php` (6 routes)
- `SUPPLIER_API_TEST.http` (comprehensive testing)

**Endpoints:**
- GET /api/suppliers - List all
- GET /api/suppliers/:id - Detail
- POST /api/suppliers - Create
- POST /api/suppliers/:id - Update
- POST /api/suppliers/:id/delete - Delete
- GET /api/suppliers/search - Search

**Documentation:** ✅ Complete  
**Testing:** ✅ All scenarios tested  
**Status:** ✅ Production ready

---

### ✅ 2. Category Management (COMPLETED)
**Files:**
- `src/models/Category.php` (14 methods)
- `src/controllers/api/CategoryApiController.php` (6 endpoints)
- `src/routes/api.php` (6 routes)
- `CATEGORY_API_TEST.http` (40 test cases)
- `CATEGORY_BACKEND_SUMMARY.md` (full documentation)

**Endpoints:**
- GET /api/categories - List all
- GET /api/categories/:id - Detail with material count
- POST /api/categories - Create
- POST /api/categories/:id - Update
- POST /api/categories/:id/delete - Delete (dengan usage check)
- GET /api/categories/search - Search

**Documentation:** ✅ Complete with 12-phase guide  
**Testing:** ✅ 40 test cases  
**Status:** ✅ Production ready

---

## 📋 FITUR DALAM PERENCANAAN

### ⬜ 3. Material Management (NEXT - HIGH PRIORITY)
**Complexity:** ⭐⭐⭐⭐ (High)  
**Priority:** P0 (Critical)  
**Estimated Time:** 5 hours  
**Dependencies:** Category ✅, Supplier ✅

#### **Database Schema:**
```sql
materials (
  id, code, name, description,
  category_id (FK → categories),
  default_supplier_id (FK → suppliers),
  unit, current_stock, min_stock,
  reorder_point, unit_price,
  is_active, created_at, updated_at
)
```

#### **Model Methods (20+ methods):**
**CRUD:**
- getAll($page, $perPage, $filters)
- findById($id)
- findByCode($code)
- create($data)
- update($id, $data)
- delete($id) - soft delete

**Business Logic:**
- updateStock($id, $quantity, $operation) - +/-
- getLowStock($threshold) - stock <= min_stock
- getOutOfStock() - stock = 0
- getStockStatus($id) - normal/warning/danger/empty
- calculateStockValue($id) - current_stock * unit_price

**Relationships:**
- getByCategory($categoryId, $page, $perPage)
- getBySupplier($supplierId, $page, $perPage)
- getWithRelations($id) - JOIN category & supplier

**Search & Filter:**
- search($keyword, $page, $perPage)
- filterByStock($status, $page, $perPage)
- advancedFilter($filters, $page, $perPage)

**Statistics:**
- getStats() - total, total_value, low_stock_count
- getCategoryDistribution()
- getTopByValue($limit)

**Validation:**
- codeExists($code, $exceptId)
- validateStock($quantity)
- canDelete($id) - check transactions

#### **Controller Endpoints (10 endpoints):**
1. `GET /api/materials` - List with filters & pagination
2. `GET /api/materials/:id` - Detail with relations
3. `GET /api/materials/code/:code` - Find by code
4. `POST /api/materials` - Create (requires Staff)
5. `POST /api/materials/:id` - Update (requires Staff)
6. `POST /api/materials/:id/delete` - Soft delete (requires Manager)
7. `GET /api/materials/search` - Advanced search
8. `GET /api/materials/low-stock` - Low stock alert
9. `GET /api/materials/category/:id` - Filter by category
10. `GET /api/materials/supplier/:id` - Filter by supplier
11. `GET /api/materials/stats` - Statistics dashboard

#### **Validation Rules:**
- `code`: required, unique, min 3, max 50, alphanumeric
- `name`: required, min 3, max 100
- `category_id`: required, exists in categories
- `default_supplier_id`: optional, exists in suppliers
- `unit`: required (Kg, Liter, Pcs, Box, dll)
- `current_stock`: numeric, >= 0
- `min_stock`: numeric, >= 0
- `reorder_point`: numeric, >= min_stock
- `unit_price`: numeric, > 0

#### **Testing Scenarios (50+ tests):**
- Authentication & authorization
- CRUD operations (success & errors)
- Stock validation (negative, zero, overflow)
- Relationship validation (invalid FK)
- Search & filter functionality
- Low stock alert
- Statistics calculation
- Code uniqueness
- Delete prevention (if has transactions)

#### **Business Rules:**
- Stock tidak boleh negatif
- Code harus unique (format: MAT-XXX-YYYY)
- Tidak bisa delete jika ada transaksi stock
- Alert jika stock <= min_stock
- Auto-calculate total value
- Track last stock update

#### **Files to Create:**
- `src/models/Material.php`
- `src/controllers/api/MaterialApiController.php`
- Update `src/routes/api.php`
- `MATERIAL_API_TEST.http`
- `MATERIAL_BACKEND_SUMMARY.md`

---

### ⬜ 4. Material Images (MEDIUM PRIORITY)
**Complexity:** ⭐⭐⭐ (Medium)  
**Priority:** P1 (High)  
**Estimated Time:** 2 hours  
**Dependencies:** Material ⬜

#### **Database Schema:**
```sql
material_images (
  id, material_id (FK → materials),
  image_url, is_primary,
  uploaded_at
)
```

#### **Model Methods:**
- create($materialId, $imageUrl, $isPrimary)
- getByMaterial($materialId)
- getPrimaryImage($materialId)
- setPrimary($imageId)
- delete($imageId)
- deleteMaterialImages($materialId)

#### **Controller Endpoints:**
1. `POST /api/materials/:id/images` - Upload image
2. `GET /api/materials/:id/images` - List images
3. `POST /api/materials/images/:id/set-primary` - Set primary
4. `DELETE /api/materials/images/:id` - Delete image

#### **Upload Logic:**
- Support: JPG, PNG, WEBP
- Max size: 5MB per file
- Max images per material: 5
- Auto resize to max 1200x1200
- Generate unique filename
- Store in: `/public/assets/uploads/materials/`
- Save relative path di database

#### **Testing:**
- Upload valid image
- Upload invalid format
- Upload oversized file
- Upload multiple images
- Set/unset primary
- Delete image
- Delete all images on material delete

---

### ⬜ 5. Stock In (Barang Masuk) (HIGH PRIORITY)
**Complexity:** ⭐⭐⭐⭐ (High)  
**Priority:** P0 (Critical)  
**Estimated Time:** 4 hours  
**Dependencies:** Material ⬜, Supplier ✅

#### **Database Schema:**
```sql
stock_in (
  id, material_id (FK → materials),
  supplier_id (FK → suppliers),
  quantity, unit_price, total_price,
  reference_number, invoice_number,
  transaction_date, notes,
  created_by (FK → users),
  created_at
)
```

#### **Model Methods:**
- create($data) - Insert + update material stock
- getAll($page, $perPage, $filters)
- findById($id) - with relations
- getByMaterial($materialId, $dateRange)
- getBySupplier($supplierId, $dateRange)
- getByDateRange($start, $end)
- getTotalByMaterial($materialId, $period)
- getStats($dateRange)
- generateReferenceNumber() - Format: SI-YYYYMMDD-XXX

#### **Controller Endpoints:**
1. `GET /api/stock-in` - List with filters
2. `GET /api/stock-in/:id` - Detail
3. `POST /api/stock-in` - Create (auto update stock)
4. `GET /api/stock-in/material/:id` - History per material
5. `GET /api/stock-in/supplier/:id` - History per supplier
6. `GET /api/stock-in/report` - Report by date range
7. `GET /api/stock-in/stats` - Statistics

#### **Business Logic:**
1. Validate quantity > 0
2. Validate unit_price > 0
3. Calculate total_price = quantity * unit_price
4. Generate unique reference_number
5. Update materials.current_stock += quantity
6. Log activity ke activity_logs
7. Track created_by user

#### **Validation Rules:**
- `material_id`: required, exists
- `supplier_id`: required, exists
- `quantity`: required, numeric, > 0
- `unit_price`: required, numeric, > 0
- `transaction_date`: required, date, <= today
- `invoice_number`: optional, max 50

#### **Testing:**
- Create valid stock in
- Validate stock update
- Test calculation (quantity * price)
- Test reference number generation
- Invalid material/supplier
- Negative quantity
- Future date prevention
- Duplicate invoice check

---

### ⬜ 6. Stock Out (Barang Keluar) (HIGH PRIORITY)
**Complexity:** ⭐⭐⭐⭐ (High)  
**Priority:** P0 (Critical)  
**Estimated Time:** 4 hours  
**Dependencies:** Material ⬜

#### **Database Schema:**
```sql
stock_out (
  id, material_id (FK → materials),
  quantity, usage_type,
  reference_number, destination,
  transaction_date, notes,
  created_by (FK → users),
  created_at
)
```

#### **Usage Types:**
- production (Produksi)
- sale (Penjualan)
- waste (Terbuang/Rusak)
- transfer (Transfer ke gudang lain)
- other (Lainnya)

#### **Model Methods:**
- create($data) - Insert + update material stock
- getAll($page, $perPage, $filters)
- findById($id)
- getByMaterial($materialId, $dateRange)
- getByUsageType($usageType, $dateRange)
- getByDateRange($start, $end)
- getTotalByMaterial($materialId, $period)
- getStats($dateRange)
- generateReferenceNumber() - Format: SO-YYYYMMDD-XXX

#### **Controller Endpoints:**
1. `GET /api/stock-out` - List with filters
2. `GET /api/stock-out/:id` - Detail
3. `POST /api/stock-out` - Create (auto update stock)
4. `GET /api/stock-out/material/:id` - History per material
5. `GET /api/stock-out/usage/:type` - Filter by usage
6. `GET /api/stock-out/report` - Report by date range
7. `GET /api/stock-out/stats` - Statistics

#### **Business Logic:**
1. Validate quantity > 0
2. **CRITICAL:** Validate quantity <= current_stock
3. Generate unique reference_number
4. Update materials.current_stock -= quantity
5. Trigger low stock alert if needed
6. Log activity
7. Track created_by user

#### **Validation Rules:**
- `material_id`: required, exists
- `quantity`: required, numeric, > 0, <= current_stock
- `usage_type`: required, enum (production, sale, waste, transfer, other)
- `transaction_date`: required, date, <= today
- `destination`: optional for some types, max 100

#### **Testing:**
- Create valid stock out
- Validate stock reduction
- Test insufficient stock (should fail)
- Test usage type validation
- Test low stock alert trigger
- Prevent negative stock
- Multiple concurrent stock out

---

### ⬜ 7. Stock Adjustment (MEDIUM PRIORITY)
**Complexity:** ⭐⭐⭐ (Medium)  
**Priority:** P1 (High)  
**Estimated Time:** 3 hours  
**Dependencies:** Material ⬜

#### **Database Schema:**
```sql
stock_adjustments (
  id, material_id (FK → materials),
  old_stock, new_stock, difference,
  reason, notes,
  adjusted_by (FK → users),
  adjusted_at
)
```

#### **Reasons:**
- count_correction (Koreksi Stok Opname)
- damage (Kerusakan)
- expiry (Kadaluarsa)
- theft (Kehilangan/Pencurian)
- system_error (Error Sistem)
- other (Lainnya)

#### **Model Methods:**
- create($data) - Calculate difference + update stock
- getAll($page, $perPage, $filters)
- findById($id)
- getByMaterial($materialId, $dateRange)
- getByReason($reason, $dateRange)
- getStats($dateRange)

#### **Controller Endpoints:**
1. `GET /api/stock-adjustments` - List
2. `GET /api/stock-adjustments/:id` - Detail
3. `POST /api/stock-adjustments` - Create adjustment
4. `GET /api/stock-adjustments/material/:id` - History
5. `GET /api/stock-adjustments/report` - Report

#### **Business Logic:**
1. Get current_stock from materials
2. Set old_stock = current_stock
3. Calculate difference = new_stock - old_stock
4. Update materials.current_stock = new_stock
5. Require reason (mandatory)
6. Log critical activity (adjustment dapat fraud)
7. Require manager approval (via RoleMiddleware)

#### **Validation Rules:**
- `material_id`: required, exists
- `new_stock`: required, numeric, >= 0
- `reason`: required, enum
- `notes`: required, min 10 (penjelasan wajib)

#### **Testing:**
- Create adjustment (increase stock)
- Create adjustment (decrease stock)
- Validate difference calculation
- Test reason validation
- Test manager role requirement
- Prevent negative new_stock

---

### ⬜ 8. Reports & Analytics (MEDIUM PRIORITY)
**Complexity:** ⭐⭐⭐⭐ (High)  
**Priority:** P1 (High)  
**Estimated Time:** 4 hours  
**Dependencies:** Material ⬜, Stock In ⬜, Stock Out ⬜

#### **Model Methods (ReportHelper):**
- getInventorySummary() - Dashboard summary
- getTransactionSummary($start, $end) - Stock in/out summary
- getMaterialTrend($materialId, $days) - Trend data untuk chart
- getCategoryDistribution() - Pie chart kategori
- getSupplierPerformance($start, $end) - Ranking supplier
- getLowStockMaterials() - Material di bawah min_stock
- getStockMovement($materialId, $start, $end) - Pergerakan stok detail
- getTopMaterials($type, $limit) - Top by value/quantity/usage
- getStockValueByCategory()

#### **Controller Endpoints:**
1. `GET /api/reports/inventory` - Dashboard summary
2. `GET /api/reports/transactions` - Transaction summary
3. `GET /api/reports/low-stock` - Low stock alert
4. `GET /api/reports/material-trend/:id` - Material trend
5. `GET /api/reports/category-distribution` - Category pie chart
6. `GET /api/reports/supplier-performance` - Supplier ranking
7. `GET /api/reports/stock-movement/:id` - Stock movement detail
8. `GET /api/reports/top-materials` - Top materials

#### **Data yang Dikembalikan:**

**Inventory Summary:**
- total_materials
- total_stock_value
- low_stock_count
- out_of_stock_count
- recent_stock_in (7 days)
- recent_stock_out (7 days)

**Transaction Summary:**
- total_stock_in (quantity & value)
- total_stock_out (quantity)
- net_change
- transaction_count
- by_material (top 10)
- by_supplier (top 10)

**Material Trend:**
- Date range data
- Stock levels per day
- Stock in per day
- Stock out per day
- Chart-ready JSON

#### **Testing:**
- Get inventory summary
- Get transaction summary
- Filter by date range
- Get low stock alert
- Get category distribution
- Get supplier performance
- Export to CSV/Excel (optional)

---

### ⬜ 9. Activity Logs (LOW PRIORITY)
**Complexity:** ⭐⭐ (Low)  
**Priority:** P2 (Medium)  
**Estimated Time:** 2 hours  
**Dependencies:** All features

#### **Database Schema:**
```sql
activity_logs (
  id, user_id (FK → users),
  action, entity_type, entity_id,
  description, ip_address, user_agent,
  created_at
)
```

#### **Actions:**
- CREATE, UPDATE, DELETE
- LOGIN, LOGOUT
- STOCK_IN, STOCK_OUT, ADJUSTMENT
- EXPORT, IMPORT

#### **Model Methods:**
- create($data)
- getAll($page, $perPage, $filters)
- getByUser($userId, $dateRange)
- getByAction($action, $dateRange)
- getByEntity($entityType, $entityId)
- getRecent($limit)
- cleanOldLogs($days) - Remove logs older than X days

#### **Controller Endpoints:**
1. `GET /api/activity-logs` - List with filters
2. `GET /api/activity-logs/user/:id` - User activity
3. `GET /api/activity-logs/action/:action` - By action
4. `GET /api/activity-logs/entity/:type/:id` - By entity
5. `GET /api/activity-logs/recent` - Recent activities

#### **Helper Function:**
```php
logActivity($action, $entityType, $entityId, $description)
```

#### **Integration:**
- Auto log di setiap Controller (CREATE/UPDATE/DELETE)
- Capture dari Auth::login/logout
- Track critical operations (stock adjustment, delete)

---

### ⬜ 10. User Management (LOW PRIORITY)
**Complexity:** ⭐⭐⭐ (Medium)  
**Priority:** P2 (Medium)  
**Estimated Time:** 3 hours  
**Dependencies:** Role & Permission ⬜

#### **Model Methods (Update User.php):**
- getAll($page, $perPage, $filters)
- findByIdWithRole($id)
- updateProfile($id, $data)
- changePassword($id, $newPassword, $oldPassword)
- updateAvatar($id, $avatarUrl)
- activate($id)
- deactivate($id)
- getStatistics()

#### **Controller Endpoints:**
1. `GET /api/users` - List users (requires Admin)
2. `GET /api/users/:id` - User profile
3. `POST /api/users/:id` - Update profile
4. `POST /api/users/:id/change-password` - Change password
5. `POST /api/users/:id/avatar` - Upload avatar
6. `POST /api/users/:id/activate` - Activate user
7. `POST /api/users/:id/deactivate` - Deactivate user
8. `GET /api/users/stats` - User statistics

#### **Validation:**
- `name`: min 3, max 100
- `email`: email format, unique
- `password`: min 6, with confirmation
- `old_password`: required for change password
- `avatar`: image format, max 2MB

#### **Security:**
- User can only update own profile
- Admin can update any profile
- Password hashing dengan bcrypt
- Verify old password before change

---

### ⬜ 11. Role & Permission Management (LOW PRIORITY)
**Complexity:** ⭐⭐⭐ (Medium)  
**Priority:** P2 (Medium)  
**Estimated Time:** 3 hours  
**Dependencies:** None

#### **Database Schema:**
```sql
roles (id, code, name, description)
permissions (id, code, name, description)
role_permissions (role_id, permission_id, is_default)
user_roles (user_id, role_id, is_default)
```

#### **Model Methods:**

**Role.php:**
- getAll()
- findById($id)
- getPermissions($roleId)
- assignPermission($roleId, $permissionId)
- removePermission($roleId, $permissionId)
- getUsersCount($roleId)

**Permission.php:**
- getAll()
- getByRole($roleId)
- getGrouped() - Group by module

#### **Controller Endpoints:**
1. `GET /api/roles` - List roles
2. `GET /api/roles/:id` - Role detail with permissions
3. `GET /api/roles/:id/permissions` - Role permissions
4. `POST /api/roles/:id/permissions` - Assign permission
5. `DELETE /api/roles/:id/permissions/:permId` - Remove permission
6. `GET /api/permissions` - List all permissions
7. `GET /api/permissions/grouped` - Permissions by module

#### **Default Roles:**
- admin (Full access)
- manager (Approve, report, manage users)
- staff (CRUD materials, stock in/out)

#### **Permission Modules:**
- materials (create, read, update, delete)
- stock (stock_in, stock_out, adjustment)
- suppliers (create, read, update, delete)
- reports (view, export)
- users (manage)

---


## 📈 DEVELOPMENT WORKFLOW

### **Standard Development Process (12 Fase):**

1. **Database Preparation**
   - Verify schema
   - Create/update tables
   - Add sample data

2. **Model Creation**
   - Create Model class
   - Implement CRUD methods
   - Add business logic methods
   - Add validation helpers

3. **Validation Rules**
   - Define validation rules
   - Implement custom validators
   - Test validation

4. **Controller Creation**
   - Create API Controller
   - Implement endpoints
   - Add authentication
   - Add authorization
   - Add error handling
   - Add activity logging

5. **Route Registration**
   - Register routes in api.php
   - Verify route order
   - Test route loading

6. **Manual Testing**
   - Test all endpoints
   - Test success scenarios
   - Test error scenarios
   - Test edge cases

7. **Database Verification**
   - Verify data integrity
   - Check timestamps
   - Check activity logs

8. **Testing File Creation**
   - Create .http file
   - Write test cases
   - Document expected results

9. **API Documentation**
   - Document endpoints
   - Document request/response
   - Document validation rules
   - Document error codes

10. **README Update**
    - Add to endpoints list
    - Update progress
    - Link to documentation

11. **Git Commit**
    - Stage files
    - Write descriptive commit
    - Push to repository

12. **Next Feature Preparation**
    - Cleanup
    - Review & reflect
    - Start next feature

---

