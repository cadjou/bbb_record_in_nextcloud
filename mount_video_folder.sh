#!/usr/bin/expect -f
set server [lindex $argv 0]
set path_l [lindex $argv 1]
set path_d [lindex $argv 2]
set passphrase [lindex $argv 3]
set path_rsa [lindex $argv 4]
set option [lindex $argv 5]
spawn -ignore HUP /usr/bin/sshfs -f $server:$path_d $path_l -o IdentityFile=$path_rsa -F $option
expect {
    "passphrase" {
        send "$passphrase\n"
        expect {
            "\n" { }
        }
    }
    "yes/no" {
        send "yes\n"
        expect {
            "passphrase" {
                send "$passphrase\n"
                expect {
                    "\n" { }
                }
            }
            "\n" { }
        }
    }
    "\n" { }
    default {
        send_user "Login failed\n"
        exit
    }
}
