Setting up L'Air du Bois on VirtualBox
======================================

## Create the `devdox` linux user on VM.

Switch to `root`.

``` bash
    $ su -
```

Create `devbox` and add it to sudoers.

``` bash
    $ usermod -aG sudo devbox
```

## Install [OpenSSH](https://www.openssh.com/) - *The SSH server*

``` bash
    $ sudo apt-get install openssh-server
```

Get VM IP address.

``` bash
    $ hostname -I
```

Now on the host you can open a terminal (replace [VM_IP] by the correct IP address).

``` bash
    $ ssh devbox@[VM_IP]
```

## Create the `ladb` mariadb user on VM.

Create MariaDB user.

``` bash
    $ sudo mysql
```

``` sql
    $ > CREATE USER 'ladb' IDENTIFIED BY 'ladb';
    $ > GRANT ALL PRIVILEGES ON *.* TO 'ladb'@localhost IDENTIFIED BY 'ladb';
    $ > FLUSH PRIVILEGES;
    $ > quit
```

## Sharing folder

On Windows :

- Install the latest version of [WinFsp](https://github.com/billziss-gh/winfsp/releases/latest).
- Install the latest version of [SSHFS-Win](https://github.com/billziss-gh/sshfs-win/releases/latest). Choose the x64 or x86 installer according to your computer’s architecture.
- Install the latest version of [SSHFS-win-manager](https://github.com/evsar3/sshfs-win-manager).

On VM machine :

Create a user special to simulate www-data over ssh.

``` bash
    $ sudo adduser www-data-sshfs -p www-data-sshfs
```

Edit `www-data-sshfs` uid and gid.

``` bash
    $ sudo nano /etc/passwd
```

Change uid and gid of www-data-sshfs user's line by `33` (the uid and gid of native www-data).

```
    # /etc/passwd

    www-data-sshfs:x:33:33::/home/www-data-sshfs:/bin/sh
```
