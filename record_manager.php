<?php
// Init message
$message = [];

// Load config
if (!is_file(__DIR__ . '/config.php')) {
	$message[] = '***********************';
	$message[] = 'Error Config';
	$message[] = 'No find ' . __DIR__ . '/config.php';
	$message[] = 'Check github https://github.com/cadjou/bbb_record_in_nextcloud';
	$message[] = '***********************';
	die_message();
}
require __DIR__ . '/config.php';

// Get init var
$path_id_rsa_default = '/root/.ssh/id_rsa_bbb_record';
// Connexion to server distant
// $server        = 'you have to be set in ' . __DIR__ . '/config.php';
$passphrase = (is_file(__DIR__ . '/.p') ? file_get_contents(__DIR__ . '/.p') : (isset($passphrase) ? $passphrase : ''));
$path_id_rsa = !empty($path_id_rsa) ? $path_id_rsa : $path_id_rsa_default;
$path_distant = !empty($path_distant) ? $path_distant : 'video';
$record_folder = !empty($record_folder) ? $record_folder : 'record';

// Variable local                         
$path_local = !empty($path_local) ? $path_local : __DIR__ . '/video';
$file_list = !empty($file_list) ? $file_list : 'list.txt';
$file_lastid = !empty($file_lastid) ? $file_lastid : 'lastid.txt';
$path_log = !empty($path_log) ? $path_log : __DIR__ . '/run.log';

// Nextcloud parameters                   
$unix_user = !empty($unix_user) ? $unix_user : 'www-data';
$unix_group = !empty($unix_group) ? $unix_group : 'www-data';
$path_nct = !empty($path_nct) ? $path_nct : '/var/www/nextcloud';

// Script path                       
$path_script = !empty($path_script) ? $path_script : __DIR__ . '/mount_video_folder.sh';
$path_ssh_option = !empty($path_ssh_option) ? $path_ssh_option : __DIR__ . '/option_ssh';

// Cron parameters                        
$install_cron = isset($install_cron) ? $install_cron : true;
$cron_minute = !empty($cron_minute) ? $cron_minute : 5;

// Path
$path_record = rtrim($path_local, '/') . '/' . trim($record_folder, '/');
$path_list = rtrim($path_local, '/') . '/' . trim($file_list, '/');

// Get file content
$list_table = is_file($path_list) ? file($path_list) : [];
$lastid = is_file($file_lastid) ? file_get_contents($file_lastid) : 0;

// If run by cron
$run_cron = isset($argv[1]);

// Load config Nextcloud
if (!is_file(rtrim($path_nct, '/') . '/config/config.php')) {
	$message[] = '***********************';
	$message[] = 'Error Path Config Nextcloud';
	$message[] = 'No find ' . $path_nct;
	$message[] = 'Check parameter $path_nct in ' . __DIR__ . '/config.php';
	$message[] = '***********************';
	die_message();
}
require rtrim($path_nct, '/') . '/config/config.php';
if (empty($CONFIG) or !is_array($CONFIG)) {
	$message[] = '***********************';
	$message[] = 'Error Config Nextcloud';
	$message[] = 'Check parameter $path_nct in ' . __DIR__ . '/config.php or your NextCloud config';
	$message[] = '***********************';
	die_message();
}

// Error Management mount network disk
if (empty($server)) {
	$message[] = '***********************';
	$message[] = 'Error Config server distant';
	$message[] = 'Check parameter $server in ' . __DIR__ . '/config.php';
	$message[] = '***********************';
	die_message();
}
// Manage rsa key
if (!$passphrase and !$run_cron) {
    $anwser = strtolower(readline("Do you want create RSA Key (Y/n): "));
    $anwser = $anwser ? $anwser : 'y';
    if ($anwser == 'y') {
        if (is_file($path_id_rsa_default)) {
            unlink($path_id_rsa_default);
        }
        if (is_file($path_id_rsa_default . '.pub')) {
            unlink($path_id_rsa_default . '.pub');
        }
        $alphabet = 'abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ 0123456789 *+-/@ ';
        $array_alpha = str_split(str_repeat($alphabet, 100));
        shuffle($array_alpha);
        $new_passphrase = '';
        foreach (array_rand($array_alpha, 60) as $key) {
            $new_passphrase .= $array_alpha[$key];
        }
        $new_passphrase = trim($new_passphrase, ' *+-/@');
        exec('ssh-keygen -t rsa -C root_bbb_record -f ' . $path_id_rsa_default . ' -P "' . $new_passphrase . '"');
        file_put_contents(__DIR__ . '/.p', $new_passphrase);
        $message[] = '*******************************************';
        $message[] = 'Copy the public key in ' . $path_id_rsa_default . '.pub the server distant in ~/.ssh/authorized_keys or ';
        $message[] = 'Run this command on the BBB server';
        $message[] = '> echo "' . trim(file_get_contents($path_id_rsa_default . '.pub')) . '" >> ~/.ssh/authorized_keys';
        $message[] = '*******************************************';
        die_message();
    }
}

// Mount network disk management
if (!is_dir($path_record)) {
    // Check package sshfs expect
    $package_sshfs = exec('dpkg -s sshfs | grep "ok installed"');
    $package_expect = exec('dpkg -s expect | grep "ok installed"');

    // Install package sshfs expect
    if (empty($package_sshfs)) {
        exec('apt-get -y install sshfs');
        $message[] = 'Install sshfs';
    }
    if (empty($package_expect)) {
        exec('apt-get -y install expect');
        $message[] = 'Install expect';
    }

    // Create script
    if (!is_file($path_script)) {
		$message[] = '***********************';
		$message[] = 'File not exist ' . $path_script;
		$message[] = 'Check github https://github.com/cadjou/bbb_record_in_nextcloud';
        $message[] = '***********************';
		die_message();
    }
	
	// Create script
    if (!is_file($path_ssh_option)) {
		$message[] = '***********************';
		$message[] = 'File not exist ' . $path_ssh_option;
		$message[] = 'Check github https://github.com/cadjou/bbb_record_in_nextcloud';
        $message[] = '***********************';
		die_message();
    }
	
    // Make executable script
    if (!is_executable($path_script)) {
        exec('chmod +x ' . escapeshellarg($path_script));
        $message[] = 'Make executable script ' . $path_script;
    }
	
    // Create local path
    if (!is_dir($path_local)) {
        exec('mkdir ' . escapeshellarg($path_local));
        $message[] = 'Create local path ' . $path_local;
    }
	
    // Execute script
    if (is_file($path_script) and is_executable($path_script)) {
        $script = $path_script . ' ' . $server . ' ' . escapeshellarg($path_local) . ' ' . 
				  escapeshellarg($path_distant) . ' "' . $passphrase . '" ' . escapeshellarg($path_id_rsa) . ' ' . escapeshellarg($path_ssh_option);
        exec($script);
        $message[] = 'Execute script > ' . $script;
    }
}

// Manage mount network disk
if (empty(exec('mountpoint ' . escapeshellarg($path_local) . ' | grep "is a mountpoint"'))) {
    $message[] = '***********************';
    $message[] = 'Unmouted ' . $path_record;
    $message[] = 'Check parameter $server / $path_distant / $passphrase in ' . __DIR__ . '/config.php';
    $message[] = '***********************';
	die_message();
}
if (!is_dir($path_record)) {
    $message[] = '***********************';
    $message[] = 'Missing ' . $path_record;
    $message[] = 'Install script in distant server or check parameter $record_folder in ' . __DIR__ . '/config.php';
    $message[] = '***********************';
	die_message();
}

// Install Cron
$crontab_container = exec('crontab -l');
$path_cron = __DIR__ . '/cron.txt';
if (count(explode(__FILE__, $crontab_container)) == 2) {
    // Remove cron
    if (!$install_cron) {
        $keep = [];
        foreach (explode("\n", $crontab_container) as $line) {
            if (count(explode(__FILE__, $line)) == 1) {
                $keep[] = $line;
            }
        }
        file_put_contents($path_cron, implode("\n", $keep));
        exec('crontab ' . $path_cron);
        unlink($path_cron);
        $message[] = 'Remove Cron';
    }
} elseif ($install_cron and !$run_cron) {
    // Add cron
    file_put_contents($path_cron, '*/' . intval($cron_minute) . ' * * * * php -f ' . __FILE__ . ' cron > /dev/null 2>&1' . "\n");
    exec('crontab ' . $path_cron);
    unlink($path_cron);
    $message[] = 'Add Cron';
}

// Database connexion
$dbh = new PDO('mysql:host=' . explode(':', $CONFIG['dbhost'])[0] . ';dbname=' . $CONFIG['dbname'], $CONFIG['dbuser'], $CONFIG['dbpassword']);

// Get storage information to get the good path
$storage = $dbh->query('select * from oc_storages where numeric_id > 0;')->fetchAll();
$storage_table = [];
foreach ($storage as $data) {
    $id_data = explode('::', $data['id']);
    if (count($id_data) >= 2) {
        list($position, $path) = $id_data;
        if ($position == 'home') {
            $storage_table[$data['numeric_id']] = rtrim($CONFIG['datadirectory'], '/') . '/' . $path;
        } elseif ($position == 'local') {
            $storage_table[$data['numeric_id']] = rtrim($path, '/');
        }
    }
}

// Get list data
$list = [];
foreach ($list_table as $data) {
    $table = explode(' ', $data);
    if (count($table) == 2) {
        $list[trim($table[0])] = trim($table[1]);
    }
}

// Manage aviable record
$tmp_list = $list;
$to_remove = [];
foreach (array_diff(scandir($path_record), ['..', '.']) as $file) {
    $tmp_list = [];
    foreach ($list as $id => $value) {
        if ($file == $value . '.mp4') {
            $message[] = 'Record found ' . $value;
            $data_file = $dbh->query('select * from oc_filecache where fileid = ' . $id)->fetchAll()[0];
            $path_file_source = $path_record . '/' . $file;
            if (isset($storage_table[$data_file['storage']])) {
                $path_file_copy = $storage_table[$data_file['storage']] . '/' . dirname($data_file['path']) . '/' . basename($data_file['path'], '.url') . '.mp4';
                if (copy($path_file_source, $path_file_copy)) {
                    $message[] = 'Copy file ' . $path_file_source . ' to ' . $path_file_copy;

                    exec('chown ' . escapeshellarg($unix_user) . ':' . escapeshellarg($unix_group) . ' ' . escapeshellarg($path_file_copy));
                    $message[] = 'Change owner file ' . $path_file_copy;

                    $table_path = explode('/', $data_file['path']);
                    if ($table_path[0] == '__groupfolders') {
                        $id_group = $table_path[1];
                        exec('sudo -u ' . escapeshellarg($unix_user) . ' php ' . escapeshellarg($path_nct) . '/occ groupfolders:scan ' . $id_group);
                        $message[] = 'Update NextCloud with sudo -u ' . $unix_user . ' php ' . $path_nct . '/occ groupfolders:scan ' . $id_group;
                    } else {
                        $short_path = substr($path_file_copy, strlen(rtrim($CONFIG['datadirectory'], '/')));
                        exec('sudo -u ' . escapeshellarg($unix_user) . ' php ' . escapeshellarg($path_nct) . '/occ files:scan --path="' . $short_path . '"');
                        $message[] = 'Update NextCloud with sudo -u ' . $unix_user . ' php ' . $path_nct . '/occ files:scan --path="' . $short_path . '"';
                    }
                    $to_remove[] = $path_file_source;
                }
            } else {
                $message[] = 'Error Storage not exist for the file ' . $data_file['path'];
            }
        } else {
            $tmp_list[$id] = $value;
        }
    }
}
$list = $tmp_list;

// Remove file record linked
foreach ($to_remove as $file) {
    unlink($file);
    $message[] = 'Remove file ' . $file;
}

// Add new record to get
$files_url = $dbh->query('select * from oc_filecache where name like "%.url" and fileid > ' . $lastid)->fetchAll();
foreach ($files_url as $data) {
    if (isset($storage_table[$data['storage']])) {
        $path = $storage_table[$data['storage']] . '/' . $data['path'];
        if (is_file($path)) {
            $check = explode('?meetingId=', file_get_contents($path));
            if (count($check) == 2 and isset($check[1])) {
                $list[$data['fileid']] = $check[1];
                $message[] = 'Add record ' . $check[1];
            }
        }
    }
}

// Get last fileid to reduice the request time
$lastid = $dbh->query('select max(fileid) from oc_filecache')->fetch()[0];
file_put_contents($file_lastid, $lastid);

// Update list
$list_table = [];
foreach ($list as $key => $meetingid) {
    $list_table[] = $key . ' ' . $meetingid;
}
if (isset($list_table)) {
    file_put_contents($path_list, implode("\n", $list_table) . "\n");
}

// Database disconnection
unset($dbh);

die_message(false);

// Message Management
function die_message($die = true){
	global $message, $run_cron, $path_log;
	
	if (!is_array($message)) $message = [$message];
	
	$path_log = !empty($path_log) ? $path_log : __DIR__ . '/run.log' ;
	
	$message_string = implode("\n", $message) . "\n";
	
	file_put_contents($path_log, $message_string,FILE_APPEND);
	
	if (!$run_cron) echo $message_string;
	
	if ($die) die();
}
