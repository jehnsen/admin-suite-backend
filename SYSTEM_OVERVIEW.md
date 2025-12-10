# **Project Charter: AdminSuite**
**"The Digital Backbone of Philippine Public Schools"**

### **1. Executive Summary**
**AdminSuite** is a cloud-based Enterprise Resource Planning (ERP) system specifically engineered for the **Administrative Officer II (AO II)** of the Department of Education (DepEd).

It replaces fragmented manual systems (logbooks, Excel files, physical folders) with a centralized command center that automates:
1.  **Personnel Management** (201 Files, Service Credits, Leave Tracking).
2.  **Property Custodianship** (Inventory, Issuances, QR Tagging).
3.  **Financial Operations** (MOOE Tracking, Cash Disbursement, Liquidation).

**Primary Goal:** To reduce the AO’s administrative workload by 60% and ensure 100% compliance with COA and CSC auditing standards.

### **2. System Blueprint (Architecture)**

This system uses a **Headless Architecture**. The frontend is decoupled from the backend to ensure speed, security, and scalability.

**The Data Flow:**
1.  **The Client (Frontend):** The AO accesses the dashboard via **Next.js 16**. It renders the UI instantly using cached data.
2.  **The Gateway (API):** Requests are sent to the **Laravel 11 API**.
3.  **The Guard (Security):** Laravel Sanctum validates the "Bearer Token" to ensure the user is authorized.
4.  **The Brain (Service Layer):** The API calculates complex logic (e.g., *Is the teacher allowed to take leave based on current credits?*).
5.  **The Vault (Database):** Data is stored in **MySQL**, with strict relationships linking *People* to *Items* and *Money*.
