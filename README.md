# esport-tournament-organization-app-php

- What is this project ?
  a platform for registration, manage teams, and conduct a esports tournament.
- Which Dependancy use in this project ?
  - PHP core
  - SQLi
  - BootStrap for Admin
  - jquery
  - other
- how to setup ?
  - use XAMPP and set database
  - import the SQL schema from `database/schema.sql`
  - update database credentials and Razorpay keys in `assets/php/config.php`

## Features
- we have two Panels.
### User Side
    - user can create his team with full validation
    - auto profile assign of team every time new
    - validation is Protected by Email confirmation code
    - user can make payment as entry fees
    - user see other teams wich submited and thier player details
    - user see winner team details
### Admin Side
    - Track Team Details
    - Manage Team Data
    - make call to team IGL
    - search team
    - verify team
    - tornament start and end 
    - manage whole frontend
## sample

- Frontend Cleint Side
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/TOURNAMENT.jpg)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(5).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(6).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(7).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(8).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(9).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(10).png)
- Admin Side

![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(1).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(2).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(3).png)
![img](https://github.com/ayushsolanki29/esport-tournament-organization-app-php/blob/main/screenshot/1(4).png)

## Database setup

1. Create a MySQL database and user.
2. Import `database/schema.sql`.
3. Update the database credentials and Razorpay keys in `assets/php/config.php`.
4. Add the Razorpay PHP SDK to `assets/razorpay/Razorpay.php` (or update the include path in the payment scripts).

The schema includes default rows for admin login (`settings` row `id=3`). Please change the admin username and password after import.
The schema also includes tables for multi-tournament management, team wallets, and tournament entries.

## Hosting on cPanel

1. **Upload files**
   - Zip the project and upload it via cPanel File Manager, then extract into `public_html`.
2. **Create database**
   - Use the *MySQL Database Wizard* to create a database and user.
3. **Import schema**
   - Open *phpMyAdmin*, select the database, and import `database/schema.sql`.
4. **Update config**
   - Edit `assets/php/config.php` with your database credentials and Razorpay keys.
5. **File permissions**
   - Ensure `assets/images/payment` is writable so payment screenshots can upload.
6. **Admin login**
   - Log in at `/admin/login.php` using the credentials from the `settings` table, then update them.
7. **Tournament management**
   - Use `/admin/tournaments.php` to create and manage multiple tournaments with entry fees, prize pools, and room details.
8. **User dashboard**
   - Users can use `/dashboard.php` to add balance, join tournaments, and view room IDs/passwords when they are released.
9. **User auth**
   - Configure Firebase in `login.php` and `register.php` for Google sign-in, or use email/password registration.
10. **User pages**
   - `/tournaments.php` (browse upcoming tournaments), `/my_tournaments.php` (your entries), `/leaderboard.php` (top winners), `/add_funds.php` (top up wallet), `/register_payment.php` (registration payment).
