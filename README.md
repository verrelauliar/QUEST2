# 🧭 Panduan Kolaborasi Tim

> Workflow GitHub untuk project coursework kita - simple, rapi, dan efisien.

---

## 🚀 Quick Reference

```
Fetch → Edit → Commit → Push
```

**Prinsip Utama:**
- 🔄 Fetch dulu sebelum mulai kerja
- 💬 Koordinasi untuk file yang sama
- ✅ Test lokal sebelum push

---

## ⚙️ Setup Project

1. Clone repo lewat **GitHub Desktop**
2. Buka project di **VS Code**
3. (Opsional) Untuk PHP, bisa taruh di `htdocs` XAMPP

Kita pakai GitHub Desktop untuk semua operasi Git - no command line needed.

---

## 🔄 Daily Workflow

### 1️⃣ Fetch
- Klik **Fetch origin** di GitHub Desktop
- Ambil update terbaru dari repo

### 2️⃣ Edit
- Buka project di VS Code
- Edit file sesuai kebutuhan
- Save changes

### 3️⃣ Commit
- Cek changes di GitHub Desktop
- Tulis commit message yang descriptive:
  - ✅ `Tambah form validation di login page`
  - ✅ `Update styling header navigation`
  - ❌ `update`, `fix`, `changes`
- Klik **Commit to main**

### 4️⃣ Push
- Klik **Push origin**
- Changes sudah tersinkron

---

## 📋 Workflow Guidelines

### File Management
- ✅ Gunakan relative paths (`./assets/style.css`)
- ✅ Nama file yang descriptive
- ❌ Avoid absolute paths (`C:\xampp\htdocs\...`)
- ❌ Hindari spasi di nama file (gunakan `_` atau camelCase)

### Commit Practices
- Commit per feature/fix yang complete
- Test di lokal sebelum push
- Gunakan `WIP: ` untuk work in progress

### Coordination
- Inform team sebelum edit file yang sedang dikerjakan
- Diskusikan dulu untuk perubahan struktur major
- Tanya dulu sebelum hapus/rename file

---

## 🚫 Files to Exclude

Jangan commit files berikut (harusnya sudah ada di `.gitignore`):

```
vendor/
node_modules/
.env
.vscode/
*.log
```

**Security:** Jangan commit credentials, API keys, atau sensitive data.

---

## ⚠️ Troubleshooting

### Conflict saat Fetch/Pull
- Jangan langsung resolve kalau belum yakin
- Koordinasi dengan team member terkait

### Error di laptop lain
- Biasanya karena beda PHP version atau XAMPP config
- Check ekstensi dan modules yang aktif

### Ragu dengan perubahan
- Better ask first daripada assume
- Team coordination > speed

---

## 🌿 Branching

Saat ini kita kerja di **main branch**.

Kalau nanti butuh parallel development, kita bisa setup feature branches.

---

## 💡 Best Practices

- 💾 Save all files di VS Code sebelum commit
- 🧪 Test functionality di lokal (XAMPP) sebelum push
- 📝 Commit messages yang clear dan specific
- 🗑️ Jangan commit generated files (logs, cache, builds)
- ⚡ Koordinasi untuk changes di core files (`index.php`, config, database)

---

## 🎯 Summary

Workflow sederhana kita:

```
1. Fetch untuk sync updates
2. Edit dan develop features
3. Commit dengan message yang jelas  
4. Push ke repo
5. Repeat
```

**Goal:** Kolaborasi yang smooth, repo yang clean, dan communication yang clear.

---

### 📌 Notes

- XAMPP location tidak affect Git selama pakai relative paths
- Kalau ada pertanyaan, discuss di group
- Keep each other updated tentang progress dan changes

**Focus:** Efficient teamwork dan maintainable codebase 🚀