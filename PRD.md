# StudentVault — Product Requirements Document

**Version:** 1.0  
**Status:** In Development  
**Project Type:** Academic Web Application  
**Framework:** Laravel  
**Frontend:** Blade, Tailwind CSS, Alpine.js  
**Database:** MySQL  
**Authentication:** Laravel Breeze  

---

# 1. Product Overview

## 1.1 Product Name

**StudentVault**

## 1.2 Description

StudentVault adalah aplikasi penyimpanan pribadi berbasis web yang ditujukan untuk mahasiswa.

Aplikasi memungkinkan pengguna menyimpan dan mengelola:

- Catatan perkuliahan
- Tugas
- Dokumen akademik
- Dokumen pribadi
- Scan identitas
- File multimedia/video
- Informasi pribadi mahasiswa

StudentVault dirancang dengan prinsip **privacy by default**.

Data sensitif tidak disimpan dalam bentuk plaintext. Proses enkripsi dan dekripsi dilakukan secara otomatis oleh backend sehingga pengguna tidak perlu memahami atau memilih algoritma kriptografi.

StudentVault menggunakan tiga algoritma enkripsi sebagai bagian dari kebutuhan akademik:

- AES
- DES
- RC4

AES merupakan algoritma utama yang direkomendasikan untuk keamanan modern, sedangkan DES dan RC4 digunakan sebagai bagian dari implementasi dan evaluasi akademik.

---

# 2. Problem Statement

Mahasiswa menyimpan berbagai informasi penting dalam perangkat dan layanan cloud, seperti:

- Catatan kuliah
- Tugas
- Scan kartu identitas
- Dokumen akademik
- Dokumen administrasi
- Video
- Informasi pribadi

Sebagian data tersebut termasuk data pribadi atau sensitif dan membutuhkan perlindungan terhadap akses yang tidak sah.

StudentVault menyediakan tempat penyimpanan pribadi yang:

1. Membatasi akses berdasarkan akun pengguna.
2. Mengenkripsi data sebelum disimpan.
3. Mendekripsi data hanya ketika pengguna yang sah membutuhkannya.
4. Memastikan file hasil dekripsi tetap identik dengan file asli.
5. Mengorganisasi data berdasarkan kebutuhan mahasiswa.

---

# 3. Product Goals

StudentVault harus:

1. Menyediakan private workspace untuk setiap pengguna.
2. Melindungi data yang disimpan menggunakan enkripsi.
3. Memastikan pengguna hanya dapat mengakses datanya sendiri.
4. Mendukung catatan dan berbagai jenis file.
5. Mengorganisasi data berdasarkan mata kuliah atau folder.
6. Menyediakan pencarian data.
7. Memungkinkan pengguna menandai data penting.
8. Menyediakan mekanisme Trash dan Restore.
9. Menampilkan status integritas file.
10. Menyediakan dashboard yang memberikan ringkasan isi StudentVault.
11. Mengimplementasikan AES, DES, dan RC4.
12. Menyediakan evaluasi performa ketiga cipher pada tahap akhir pengembangan.

---

# 4. Non-Goals

StudentVault bukan:

- Public file sharing service.
- Social media.
- Collaboration platform.
- Public cloud storage.
- File encryption tool yang meminta pengguna memilih algoritma.
- Production password manager.

Pengguna tidak perlu mengetahui cipher yang digunakan untuk setiap operasi.

---

# 5. Target Users

Target utama adalah:

**Mahasiswa**

yang membutuhkan tempat pribadi untuk menyimpan dan mengorganisasi data akademik dan pribadi.

Contoh penggunaan:

> Seorang mahasiswa menyimpan catatan Machine Learning, tugas Information Security, scan kartu identitas, dokumen administrasi, dan video presentasi di StudentVault.

Semua data tersebut hanya dapat diakses oleh mahasiswa tersebut setelah login.

---

# 6. Core Design Principles

## 6.1 Privacy by Default

Semua data sensitif harus dilindungi sebelum disimpan.

## 6.2 User Isolation

Setiap resource harus mempunyai owner.

Contoh:

```text
User A
├── Notes A
├── Files A
└── Courses A

User B
├── Notes B
├── Files B
└── Courses B
```

User A tidak boleh mengakses resource milik User B.

---

## 6.3 Transparent Encryption

Pengguna tidak memilih:

```text
AES
DES
RC4
```

Encryption policy ditentukan oleh backend.

Contoh:

```text
User
 ↓
Upload Assignment
 ↓
StudentVault
 ↓
Encryption Policy
 ↓
Cipher
 ↓
Encrypted Storage
```

---

## 6.4 No Plaintext Storage

Data yang dikategorikan sensitif tidak boleh disimpan secara permanen sebagai plaintext.

Untuk file:

```text
Original File
     ↓
Encryption
     ↓
Encrypted .vault
     ↓
Private Storage
```

File asli tidak disimpan secara permanen.

---

# 7. Technology Stack

## Backend

- Laravel
- PHP 8.1+
- Laravel Breeze

## Frontend

- Blade
- Tailwind CSS
- Alpine.js

## Database

- MySQL

## Cryptography

- PHP OpenSSL
- phpseclib 4

## Storage

Laravel private/local storage.

Encrypted files disimpan di:

```text
storage/app/private/vault/
```

---

# 8. Application Architecture

High-level architecture:

```text
Browser
   │
   ▼
Laravel Routes
   │
   ▼
Authentication Middleware
   │
   ▼
Controllers
   │
   ├───────────────┐
   ▼               ▼
Models          Services
                   │
            ┌──────┴───────┐
            ▼              ▼
      FileVaultService  EncryptionManager
                              │
                    ┌─────────┼─────────┐
                    ▼         ▼         ▼
                   AES       DES       RC4
                    │         │         │
                    └─────────┼─────────┘
                              ▼
                       Private Storage
```

---

# 9. Existing Features

Fitur berikut sudah menjadi bagian dari fondasi StudentVault.

## 9.1 Authentication

User dapat:

- Register
- Login
- Logout
- Mengelola profile melalui fitur Laravel yang tersedia

Protected routes menggunakan:

```text
auth middleware
```

---

## 9.2 Notes

User dapat:

- Membuat note
- Membaca note
- Mengedit note
- Menghapus note

Note memiliki:

```text
title
content
user_id
timestamps
```

Title dan content disimpan dalam bentuk terenkripsi.

Cipher utama:

```text
AES-256-GCM
```

---

## 9.3 File Vault

User dapat:

- Upload file
- Melihat daftar file
- Download file
- Menghapus file

Metadata file meliputi:

```text
user_id
original_name
stored_name
category
cipher
mime_type
original_size
original_hash
timestamps
```

File ciphertext disimpan sebagai:

```text
UUID.vault
```

---

# 10. Encryption Architecture

StudentVault menggunakan abstraction layer:

```text
CipherInterface
      │
      ├── AesCipher
      ├── DesCipher
      └── Rc4Cipher
```

Cipher diakses melalui:

```text
EncryptionManager
```

Encrypted payload harus menyimpan metadata cipher.

Conceptual format:

```json
{
    "version": 1,
    "cipher": "aes",
    "data": {}
}
```

EncryptionManager harus dapat membaca cipher dari payload ketika melakukan dekripsi.

---

# 11. Encryption Algorithms

## 11.1 AES

Implementation:

```text
AES-256-GCM
```

Karakteristik:

- Modern
- Authenticated encryption
- Random IV
- Authentication tag
- Cipher utama StudentVault

Digunakan untuk data yang membutuhkan perlindungan kuat.

---

## 11.2 DES

Implementation:

```text
DES-CBC
```

Menggunakan:

- Random IV
- CBC mode

ECB tidak boleh digunakan.

DES dianggap obsolete dan digunakan untuk kebutuhan akademik dan perbandingan.

---

## 11.3 RC4

Implementation:

```text
RC4 Stream Cipher
```

RC4 tidak menggunakan block mode.

RC4 merupakan algoritma legacy dan digunakan untuk kebutuhan akademik.

---

# 12. Encryption Policy

Pengguna tidak memilih cipher.

Backend menggunakan `EncryptionPolicy`.

Current policy:

| Category | Cipher |
|---|---|
| Notes | AES |
| Documents | AES |
| Identity | AES |
| Assignments | DES |
| Videos | RC4 |

Policy dapat berubah tanpa mengubah UI.

DES dan RC4 tidak boleh dipresentasikan sebagai algoritma yang direkomendasikan untuk sistem produksi.

---

# 13. Dashboard

## 13.1 Objective

Dashboard menjadi halaman utama setelah user login.

Dashboard harus memberikan gambaran cepat mengenai isi StudentVault.

---

## 13.2 Dashboard Statistics

Dashboard menampilkan minimal:

### Total Notes

```text
12 Notes
```

### Total Files

```text
8 Files
```

### Storage Usage

Contoh:

```text
34.2 MB
```

Storage dihitung berdasarkan file milik user.

### Courses

Setelah fitur Courses tersedia:

```text
4 Courses
```

---

## 13.3 Recent Notes

Tampilkan beberapa note terakhir.

Contoh:

```text
Recent Notes

Machine Learning
Information Security
Database Systems
```

Klik note membuka detail note.

---

## 13.4 Recent Files

Tampilkan beberapa file terakhir.

Contoh:

```text
Recent Files

assignment-1.pdf
lecture-notes.docx
presentation.mp4
```

---

## 13.5 Quick Actions

Dashboard menyediakan:

```text
+ New Note

↑ Upload File
```

Setelah Course tersedia:

```text
+ New Course
```

---

# 14. Main Navigation

Desktop menggunakan sidebar.

Target navigation:

```text
STUDENTVAULT

Dashboard

MY SPACE
├── Notes
├── Files
├── Courses
└── Starred

PERSONAL
├── Personal Vault
└── Trash

ACCOUNT
└── Profile
```

Active page harus terlihat jelas.

---

# 15. Courses / Folders

## 15.1 Objective

Mahasiswa dapat mengorganisasi Notes dan Files berdasarkan mata kuliah atau project.

Contoh:

```text
Machine Learning
├── Notes
└── Files

Information Security
├── Notes
└── Files

Database Systems
├── Notes
└── Files
```

---

## 15.2 Course Data

Course minimal mempunyai:

```text
id
user_id
name
description
timestamps
```

Optional future fields:

```text
code
semester
lecturer
```

---

## 15.3 Course Operations

User dapat:

- Create course
- View course
- Edit course
- Delete course

User hanya dapat melihat course miliknya.

---

## 15.4 Notes Integration

Note dapat mempunyai:

```text
course_id
```

Course bersifat optional.

Artinya note dapat berada:

```text
General Notes
```

atau:

```text
Machine Learning
```

---

## 15.5 File Integration

VaultFile dapat mempunyai:

```text
course_id
```

Optional.

Contoh:

```text
Machine Learning

Notes
├── KNN
└── Decision Tree

Files
├── Assignment.pdf
└── Dataset.csv
```

---

# 16. Search

## 16.1 Objective

User dapat menemukan data dengan cepat.

Global search dapat mencari:

- Notes
- Files
- Courses

---

## 16.2 Search Security Constraint

Encrypted content tidak boleh dibuat plaintext hanya demi mempermudah database search.

Pada implementasi awal, search dapat difokuskan pada metadata yang aman dan relevan.

Search tidak boleh menghasilkan resource milik user lain.

---

# 17. Starred / Favorites

User dapat menandai resource penting.

Supported resources:

- Notes
- Files

Contoh:

```text
★ Machine Learning Cheat Sheet
★ ETS Material.pdf
```

Starred page menampilkan semua resource penting milik user.

---

# 18. Personal Information Vault

## 18.1 Objective

Menyimpan informasi pribadi mahasiswa yang membutuhkan perlindungan lebih tinggi.

Contoh data:

- Student ID
- University information
- Emergency information
- Personal documents
- Identity scans

Data sensitif menggunakan AES.

---

## 18.2 Security

Personal Vault harus:

- Membutuhkan authentication.
- Hanya dapat diakses owner.
- Menyimpan field sensitif dalam ciphertext.
- Tidak mengekspos plaintext di database.

---

# 19. Trash & Restore

Notes dan files tidak langsung dihapus secara permanen dari UI.

Gunakan soft delete.

Flow:

```text
Delete
  ↓
Trash
  ↓
┌────────────┐
│            │
Restore   Delete Forever
```

---

## 19.1 Trash Page

Trash menampilkan:

- Deleted Notes
- Deleted Files

User dapat:

```text
Restore
Delete Permanently
```

---

## 19.2 File Deletion

Ketika file masuk Trash:

```text
.vault file
```

tidak langsung dihapus dari storage.

Ketika:

```text
Delete Permanently
```

baru:

1. Ciphertext dihapus dari storage.
2. Database record dihapus permanen.

---

# 20. File Detail

Setiap file memiliki halaman detail.

Contoh:

```text
assignment.pdf

Category
Assignment

Type
application/pdf

Size
2.4 MB

Uploaded
25 October 2026

Integrity
✓ Verified

Actions

Download
Delete
```

Cipher tidak perlu ditampilkan pada interface user utama.

---

# 21. Integrity Verification

Setiap file mempunyai SHA-256 hash dari file asli.

Saat file didekripsi:

```text
Encrypted File
      ↓
Decrypt
      ↓
Plaintext
      ↓
SHA-256
      ↓
Compare
      ↓
Original Hash
```

Jika sama:

```text
Integrity Verified
```

Jika berbeda:

```text
Integrity Verification Failed
```

File yang gagal integrity verification tidak boleh dianggap valid.

---

# 22. Authorization Requirements

Setiap protected resource harus mempunyai ownership validation.

Contoh:

```text
vault_file.user_id === authenticated_user.id
```

Jika tidak:

```text
HTTP 403 Forbidden
```

Berlaku untuk:

- Notes
- Files
- Courses
- Starred resources
- Personal Vault
- Trash

Tidak boleh hanya mengandalkan resource ID di URL.

---

# 23. File Upload Requirements

Supported use cases minimal:

- PDF
- Office documents
- Images / identity scans
- Video

Kategori:

```text
documents
identity
assignments
videos
```

Initial maximum file size:

```text
50 MB
```

Validation MIME/type dapat diperketat berdasarkan kategori.

---

# 24. Storage Requirements

Encrypted files disimpan di private storage.

Tidak boleh disimpan pada:

```text
public/
```

dan tidak boleh dapat diakses langsung menggunakan public URL.

Correct flow:

```text
GET /vault/{file}/download
        ↓
Authentication
        ↓
Authorization
        ↓
Read ciphertext
        ↓
Decrypt
        ↓
Integrity check
        ↓
HTTP download
```

---

# 25. Database Overview

Target entities:

```text
users
 │
 ├── notes
 │
 ├── vault_files
 │
 ├── courses
 │
 └── personal_data
```

Relationships:

```text
User
├── hasMany Notes
├── hasMany VaultFiles
└── hasMany Courses

Course
├── belongsTo User
├── hasMany Notes
└── hasMany VaultFiles

Note
├── belongsTo User
└── belongsTo Course (optional)

VaultFile
├── belongsTo User
└── belongsTo Course (optional)
```

---

# 26. Security Requirements

StudentVault harus mengikuti aturan berikut.

### SR-01 Authentication

Protected data hanya dapat diakses authenticated user.

### SR-02 Authorization

User hanya dapat mengakses resource miliknya.

### SR-03 Encryption at Rest

Sensitive content harus disimpan terenkripsi.

### SR-04 Private File Storage

File ciphertext tidak boleh public.

### SR-05 No ECB

ECB tidak boleh digunakan untuk block cipher.

### SR-06 Random IV

Cipher yang membutuhkan IV harus menggunakan cryptographically secure random IV.

### SR-07 Key Separation

AES, DES, dan RC4 menggunakan key berbeda.

### SR-08 Environment Keys

Encryption key tidak boleh hardcoded di source code.

Keys disimpan melalui environment configuration.

### SR-09 Integrity Verification

File yang telah didekripsi diverifikasi menggunakan SHA-256.

AES-GCM juga menggunakan authentication tag untuk autentikasi ciphertext AES.

### SR-10 Secure Failure

Jika decryption atau integrity verification gagal, aplikasi tidak boleh mengembalikan file seolah-olah valid.

---

# 27. UI/UX Requirements

StudentVault harus mempunyai tampilan sederhana dan modern.

Design direction:

- Minimal
- Clean
- Student-oriented
- Responsive
- Dashboard-centric
- Consistent spacing
- Clear typography

Gunakan Tailwind CSS.

---

## 27.1 Desktop

Gunakan sidebar.

```text
┌──────────────┬─────────────────────────────┐
│ StudentVault │ Page Header                 │
│              ├─────────────────────────────┤
│ Dashboard    │                             │
│ Notes        │          Content            │
│ Files        │                             │
│ Courses      │                             │
│ Starred      │                             │
│ Trash        │                             │
│ Profile      │                             │
└──────────────┴─────────────────────────────┘
```

---

## 27.2 Mobile

Sidebar berubah menjadi collapsible navigation.

---

# 28. Empty States

Setiap fitur harus mempunyai empty state.

Contoh Notes:

```text
No notes yet.

Create your first note to start documenting
your studies.

[Create Note]
```

Files:

```text
Your vault is empty.

Securely store your first document.

[Upload File]
```

Courses:

```text
No courses yet.

Create a course to organize your notes
and files.

[Create Course]
```

---

# 29. Error Handling

User harus mendapatkan pesan yang jelas ketika:

- Upload gagal
- File terlalu besar
- File tidak ditemukan
- Decryption gagal
- Integrity verification gagal
- Resource tidak authorized
- Category tidak valid

Internal cryptographic detail tidak perlu ditampilkan kepada user.

Contoh:

```text
Unable to open this file securely.
```

lebih baik daripada mengekspos stack trace atau key-related error.

---

# 30. Benchmark Module — Deferred

Benchmark bukan prioritas implementasi saat ini.

Fitur ini dikerjakan setelah core StudentVault selesai.

Benchmark akan membandingkan:

```text
AES
DES
RC4
```

menggunakan input yang sama.

Metrics minimal:

- Encryption time
- Decryption time
- Throughput
- Ciphertext size
- Storage overhead
- Integrity verification

Benchmark harus melakukan beberapa kali percobaan agar pengukuran lebih reliable.

---

# 31. Benchmark Integrity

Untuk setiap cipher:

```text
Original
   ↓
SHA-256 A

Original
   ↓
Encrypt
   ↓
Decrypt
   ↓
SHA-256 B
```

Requirement:

```text
SHA-256 A === SHA-256 B
```

---

# 32. Benchmark Security Interpretation

Hasil benchmark tidak boleh dinilai berdasarkan speed saja.

Final analysis harus mempertimbangkan:

```text
Performance
+
Security
+
Storage overhead
+
Suitability
```

Kesimpulan harus menjelaskan bahwa algoritma tercepat belum tentu merupakan algoritma yang paling aman.

DES dan RC4 harus diidentifikasi sebagai algoritma legacy/obsolete.

---

# 33. Development Roadmap

## Phase 1 — Foundation

Status: **Completed**

- [x] Laravel project
- [x] Laravel Breeze
- [x] Authentication
- [x] Database
- [x] User model

---

## Phase 2 — Notes

Status: **Completed**

- [x] Notes CRUD
- [x] Ownership
- [x] AES encryption
- [x] Automatic decryption

---

## Phase 3 — Encryption Layer

Status: **Completed**

- [x] CipherInterface
- [x] EncryptionManager
- [x] AES-256-GCM
- [x] DES-CBC
- [x] RC4
- [x] EncryptionPolicy
- [x] Cipher metadata envelope

---

## Phase 4 — File Vault

Status: **Completed**

- [x] VaultFile model
- [x] Upload
- [x] Encryption
- [x] Private storage
- [x] Download
- [x] Decryption
- [x] SHA-256 verification
- [x] Delete
- [x] MIME/category validation improvements
- [x] Large-file handling improvements

---

## Phase 5 — Application UI

Status: **Completed**

- [x] StudentVault application layout
- [x] Sidebar
- [x] Dashboard
- [x] Statistics cards
- [x] Recent notes
- [x] Recent files
- [x] Quick actions
- [x] Responsive navigation

---

## Phase 6 — Organization

Status: **Completed**

- [x] Course model
- [x] Course CRUD
- [x] Assign Notes to Course
- [x] Assign Files to Course
- [x] Course detail page
- [x] General/unassigned resources

---

## Phase 7 — Productivity

- [ ] Search
- [ ] Starred Notes
- [ ] Starred Files
- [ ] Filter files by category
- [ ] Sort resources

---

## Phase 8 — Personal Vault

- [ ] Personal information model
- [ ] Encrypted personal fields
- [ ] Identity document integration
- [ ] Personal Vault UI

---

## Phase 9 — Trash

- [ ] Soft delete Notes
- [ ] Soft delete Files
- [ ] Trash page
- [ ] Restore
- [ ] Permanent deletion
- [ ] Delete ciphertext after permanent deletion

---

## Phase 10 — Security Improvements

- [ ] Laravel Policies
- [ ] File detail page
- [ ] Integrity status
- [ ] Better upload validation
- [ ] Error handling
- [ ] Security review
- [ ] Authorization testing

---

## Phase 11 — Benchmark

Deferred until core application completion.

- [ ] BenchmarkService
- [ ] AES benchmark
- [ ] DES benchmark
- [ ] RC4 benchmark
- [ ] Repeated measurements
- [ ] Encryption time
- [ ] Decryption time
- [ ] Throughput
- [ ] Storage overhead
- [ ] Integrity verification
- [ ] Benchmark dashboard
- [ ] Charts
- [ ] Result interpretation

---

## Phase 12 — Finalization

- [ ] Responsive testing
- [ ] UI polish
- [ ] Functional testing
- [ ] Security testing
- [ ] Cross-user authorization testing
- [ ] Final documentation
- [ ] Screenshots
- [ ] Demo preparation
- [ ] Final report

---

# 34. Definition of Done

StudentVault dianggap selesai ketika:

### Authentication

- [ ] User dapat register/login/logout.
- [ ] Protected page tidak dapat diakses guest.

### Notes

- [ ] User dapat CRUD Notes.
- [ ] Note disimpan encrypted.
- [ ] User lain tidak dapat mengakses note.

### File Vault

- [ ] User dapat upload file.
- [ ] File disimpan sebagai ciphertext.
- [ ] Plaintext tidak disimpan permanen.
- [ ] File dapat didekripsi dan didownload.
- [ ] Downloaded file identik dengan original.
- [ ] Integrity diverifikasi.
- [ ] User lain tidak dapat mengakses file.

### Organization

- [x] User dapat membuat Course.
- [x] Notes dapat diorganisasi berdasarkan Course.
- [x] Files dapat diorganisasi berdasarkan Course.

### Productivity

- [ ] Search bekerja.
- [ ] Starred bekerja.
- [ ] Trash dan Restore bekerja.

### Security

- [ ] AES tersedia.
- [ ] DES tersedia.
- [ ] RC4 tersedia.
- [ ] ECB tidak digunakan.
- [ ] Encryption keys tidak hardcoded.
- [ ] Private files tidak memiliki public URL.
- [ ] Authorization diterapkan pada seluruh resource.

### Benchmark

- [ ] Input yang sama diuji menggunakan AES, DES, dan RC4.
- [ ] Encryption/decryption time tercatat.
- [ ] Throughput dihitung.
- [ ] Integrity diverifikasi.
- [ ] Hasil divisualisasikan.
- [ ] Hasil diinterpretasikan berdasarkan performa dan keamanan.

---

# 35. Final Product Vision

Final StudentVault:

```text
                         STUDENTVAULT
                              │
             ┌────────────────┼─────────────────┐
             │                │                 │
         Dashboard          Courses         Personal
             │                │               Vault
      ┌──────┴──────┐    ┌────┴────┐           │
      │             │    │         │           │
    Notes          Files Notes    Files     Sensitive
      │             │    │         │          Data
      └──────┬──────┘    └────┬────┘           │
             │                │                 │
             └────────────────┼─────────────────┘
                              │
                    Encryption Services
                              │
                 ┌────────────┼────────────┐
                 │            │            │
                 ▼            ▼            ▼
                AES          DES          RC4
                 │            │            │
                 └────────────┼────────────┘
                              │
                       Private Storage
```

StudentVault harus terasa sebagai **private academic workspace untuk mahasiswa**, bukan sekadar demonstrasi algoritma enkripsi.

Kriptografi bekerja di belakang aplikasi, sedangkan pengguna berinteraksi dengan fitur yang familiar seperti Notes, Courses, Files, Search, Starred, dan Trash.

Benchmark AES/DES/RC4 menjadi bagian evaluasi teknis di tahap akhir, bukan fokus utama pengalaman pengguna.
