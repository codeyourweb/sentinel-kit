# Create Sentinel Kit admin users

Once installed and operational, the Sentinel Kit administration interface is accessible by default at https://sentinel-kit.local. No web recording functionality is implemented, separating the functions of the platform administrator and the analyst who operates it.
![Sentinel-Kit login page](sentinel-kit_login.png)

## Users management

Every user management operations are done inside the backend container. You can connect to it like this:
```bash
docker exec -it sentinel-kit-app-backend /bin/bash
```

a console utility can be called with `php bin/console`. If you want to create a new user, just type the following command:

```bash
# app:users:create <email> <password>
backend:/var/www/html# php bin/console app:users:create demo@example.com MyPassword!

 [OK] User successfully created                                                                                         
                                                                                                                        
==============================
email: demo@example.com
OTP authenticator URL: https://backend.sentinel-kit.local/qrcode/b3RwYXV0aDovL3RvdHAvRGF0YU1vbml0b3IlMjBzZXJ2ZXIlM0FkZXYuanAuZ2FybmllciU0MGdtYWlsLmNvbSU0MERhdGFNb25pdG9yP2lzc3Vlcj1EYXRhTW9uaXRvciUyMHNlcnZlciZzZWNyZXQ9SzQzVVlIR1NTQlNETVlHU1hTQVNKN0ZDM0FVSTdFQTM3SExaN1c3QkhZUkZPNzM3VE9WUQ==    
==============================
```
OTP is directly available if the admin wants to store it. You don't need to comunicate it to the end user, he will access it on the first successful login on the platform.
![Login OTP form](img/sentinel-kit_login_otp.png)

## Listing users
Existing user accounts can be listed like this

```bash
backend:/var/www/html# php bin/console app:users:list
User ID: 1 | Email: demo@example.com
```

## Remove users
You can delete an user providing its ID or email like this
```bash
backend:/var/www/html# php bin/console app:users:delete 1

 [OK] User deleted successfully.                                                                                        
                                                                                                                        
```

## Users secrets reset
Password resetting could be done
```bash
backend:/var/www/html# php bin/console app:users:renew-password demo@example.com MyNewPa$$w0rd

 [OK] User password reset successful.                                                                                   
```                                                                                                                        

And you can also reset user OTP:
```bash
backend:/var/www/html# php bin/console app:users:renew-otp demo@example.com

 [OK] User OTP reset successful.                                                                                        
                                                                                                                      
==============================
email: demo@example.com
OTP authenticator URL: https://localhost/qrcode/b3RwYXV0aDovL3RvdHAvRGF0YU1vbml0b3IlMjBzZXJ2ZXIlM0FkZW1vJTQwZXhhbXBsZS5jb20lNDBEYXRhTW9uaXRvcj9pc3N1ZXI9RGF0YU1vbml0b3IlMjBzZXJ2ZXImc2VjcmV0PVgzT1M3QVdDQ1Q3SUZISktCQ0JUUlczVjRWQlgzWDZCN1hLQ1I2NU5JRUZQTDVBNVVPR0E=
```

Your users can now login on the platform 
![Sentinel-Kit Homepage](img/sentinel-kit_server_features.png)