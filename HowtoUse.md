Only tested on Linux!

# Linux #
**Step 1**

Open terminal and go to your psop directory:
```
cd /var/www/psop/
```

**Step 2 (Only First run)**

First, correct the permissions to ./psop file and cache/ dir:
```
chmod +x ./psop.php;
chmod 777 ./cache/;
```

**Step 3**

Then run Psop on your project (Psop will not overwrite your project, processed files can be accessed in ./cache/):
```
./psop --dir /var/www/myproject/
```