# Multi-Tier Web Application Deployment on AWS

A highly available, auto-scaled PHP web application deployed on AWS using EC2, RDS MySQL, Auto Scaling, and an Application Load Balancer.

---

## Architecture Overview

```
Internet
    │
    ▼
Application Load Balancer (techwave-alb)
    │
    ├──▶ EC2 Instance 1 (us-east-1a)
    │         │
    └──▶ EC2 Instance 2 (us-east-1b)
              │
              ▼
        RDS MySQL (techwave-db)
        Database: intel
        Table: data
```

---

## AWS Services Used

- **Amazon EC2** — Virtual servers running Apache + PHP
- **Amazon RDS** — Managed MySQL database
- **Auto Scaling Group** — Maintains minimum 2 EC2 instances for high availability
- **Application Load Balancer** — Distributes incoming traffic across EC2 instances
- **Security Groups** — Controls traffic between ALB, EC2, and RDS

---

## Tech Stack

- **OS:** Amazon Linux 2023
- **Web Server:** Apache HTTP Server (httpd)
- **Backend:** PHP 8.5
- **Database:** MySQL (via Amazon RDS)
- **Infrastructure:** AWS (EC2, RDS, ALB, ASG)

---

## Project Structure

```
multi-tier-web-app-aws/
├── website/
│   ├── index.php           # Main PHP application
│   └── config.sample.php   # Sample DB config (no credentials)
├── scripts/
│   └── install.sh          # EC2 setup script
├── .gitignore
└── README.md
```

> `config.php` is excluded from the repository via `.gitignore` to protect database credentials.

---

## Setup Guide

### Prerequisites
- AWS Account
- PuTTY and PuTTYgen (for SSH on Windows)
- Git installed locally

---

### Step 1 — Launch EC2 Instance

- **AMI:** Amazon Linux 2023
- **Instance type:** t3.micro
- **Security Group:** `techwave-ec2-sg`
  - SSH | Port 22 | My IP
  - HTTP | Port 80 | 0.0.0.0/0
  - All traffic | All | 0.0.0.0/0
- **Key pair:** Download `.pem` → convert to `.ppk` using PuTTYgen

---

### Step 2 — Install Dependencies on EC2

SSH into EC2 via PuTTY and run:

```bash
sudo yum update -y
sudo yum install -y httpd php php-mysqli mariadb105
sudo systemctl start httpd
sudo systemctl enable httpd
```

---

### Step 3 — Deploy Application

Upload files to EC2 using PSCP:

```bash
pscp -i "path/to/key.ppk" website/index.php ec2-user@YOUR_EC2_IP:/home/ec2-user/
pscp -i "path/to/key.ppk" website/config.php ec2-user@YOUR_EC2_IP:/home/ec2-user/
```

Move to Apache web directory:

```bash
sudo mv /home/ec2-user/index.php /var/www/html/index.php
sudo mv /home/ec2-user/config.php /var/www/html/config.php
```

---

### Step 4 — Create RDS Instance

- **Engine:** MySQL
- **Instance class:** db.t3.micro
- **DB instance identifier:** `techwave-db`
- **Master username:** `admin`
- **Initial database name:** `intel`
- **Security Group:** `techwave-rds-sg`
  - MySQL/Aurora | Port 3306 | Source: `techwave-ec2-sg`
- **Public access:** No

---

### Step 5 — Create Database and Table

SSH into EC2 and connect to RDS:

```bash
mysql -h YOUR_RDS_ENDPOINT -u admin -p
```

Run the following SQL:

```sql
USE intel;

CREATE TABLE data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    city VARCHAR(100)
);

INSERT INTO data (name, city) VALUES ('Alice', 'New York');
INSERT INTO data (name, city) VALUES ('Bob', 'Mumbai');

SELECT * FROM data;
```

---

### Step 6 — Configure Database Connection

Create `config.php` on the EC2 instance at `/var/www/html/config.php`:

```php
<?php
define('DB_HOST', 'YOUR_RDS_ENDPOINT');
define('DB_USER', 'admin');
define('DB_PASS', 'your_password');
define('DB_NAME', 'intel');
?>
```

> Use `config.sample.php` as a reference.

---

### Step 7 — Enable Auto Scaling

**Create AMI:**
- EC2 → Select instance → Actions → Image and templates → Create image
- Name: `techwave-ami`

**Create Launch Template:**
- AMI: `techwave-ami`
- Instance type: t3.micro
- Security group: `techwave-ec2-sg`
- Name: `techwave-lt`

**Create Auto Scaling Group:**
- Launch template: `techwave-lt`
- VPC: Default VPC
- Subnets: Select 2 subnets in different Availability Zones
- Load balancer: Create new Application Load Balancer (`techwave-alb`)
- Scheme: Internet-facing | Port 80
- Target group: `techwave-tg`
- Desired: 2 | Minimum: 2 | Maximum: 4

---

## Security Group Configuration

| Security Group | Rule | Port | Source |
|---|---|---|---|
| techwave-ec2-sg | SSH | 22 | My IP |
| techwave-ec2-sg | HTTP | 80 | 0.0.0.0/0 |
| techwave-rds-sg | MySQL/Aurora | 3306 | techwave-ec2-sg |

---

## Verification

1. Open Load Balancer DNS in browser
2. Website displays data from RDS MySQL
3. Refresh the page — **Server Hostname changes** between two EC2 instances, confirming Auto Scaling and Load Balancer are working correctly

---

## Key Concepts Demonstrated

- **High Availability** — Minimum 2 EC2 instances across multiple Availability Zones
- **Auto Scaling** — Automatically adjusts capacity based on demand
- **Multi-Tier Architecture** — Separate web and database layers
- **Security Best Practices** — EC2 to RDS access via security group reference, credentials excluded from version control
- **Load Balancing** — ALB distributes traffic evenly across instances