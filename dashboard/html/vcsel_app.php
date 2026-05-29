<?php
//Developer:    Pertech
//Created:      2026.05.29
//Revision:     2026.05.29

require_once('common/includes/std_lib.inc.php');

$page_title='VCSEL App';
$server_file_check=array(__FILE__);

function vcsel_app_download($folder,$extensions){
    $base_dir=__DIR__ . '/downloads/' . $folder;
    if(!is_dir($base_dir)){
        return false;
    }

    foreach($extensions as $extension){
        $matches=glob($base_dir . '/*.' . $extension);
        if(!empty($matches)){
            $path=$matches[0];
            return array(
                'name'=>basename($path),
                'url'=>'downloads/' . rawurlencode($folder) . '/' . rawurlencode(basename($path)),
                'size'=>filesize($path)
            );
        }
    }

    return false;
}

function vcsel_app_size($bytes){
    if($bytes===false || $bytes<=0){
        return '';
    }

    $units=array('B','KB','MB','GB');
    $size=$bytes;
    $unit=0;
    while($size>=1024 && $unit<count($units)-1){
        $size=$size/1024;
        $unit++;
    }

    return round($size,1) . ' ' . $units[$unit];
}

function vcsel_app_logo($platform){
    switch($platform){
        case 'mac':
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M44.5 33.8c-.1-7.1 5.8-10.6 6.1-10.7-3.4-4.9-8.5-5.6-10.3-5.7-4.3-.4-8.5 2.6-10.7 2.6-2.2 0-5.7-2.5-9.4-2.4-4.8.1-9.2 2.8-11.7 7.1-5 8.7-1.3 21.5 3.6 28.5 2.4 3.4 5.2 7.2 8.9 7.1 3.6-.1 4.9-2.3 9.2-2.3 4.3 0 5.5 2.3 9.3 2.2 3.8-.1 6.2-3.5 8.5-6.9 2.7-3.9 3.8-7.7 3.9-7.9-.1-.1-7.3-2.9-7.4-11.6z"/><path d="M37.5 12.8c2-2.4 3.3-5.7 2.9-9-2.8.1-6.3 1.9-8.3 4.2-1.8 2.1-3.4 5.5-3 8.7 3.2.3 6.4-1.6 8.4-3.9z"/></svg>';
        case 'windows':
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M6 13.2l22.7-3.1v21.8H6V13.2zm25.3-3.5L58 6v25.9H31.3V9.7zM6 34.7h22.7v21.7L6 53.3V34.7zm25.3 0H58V60l-26.7-3.7V34.7z"/></svg>';
        case 'linux':
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 4c-7.2 0-13 6.4-13 14.3 0 4.4 1.1 7.4 2.5 10.1-3.6 4.5-6.4 10.9-7.8 18.3-.7 3.5 1.8 6.8 5.4 6.8 2.4 0 4.3-1.5 5.1-3.7 1.7.8 4.3 1.4 7.8 1.4s6.1-.6 7.8-1.4c.8 2.2 2.7 3.7 5.1 3.7 3.6 0 6.1-3.3 5.4-6.8-1.4-7.4-4.2-13.8-7.8-18.3 1.4-2.7 2.5-5.7 2.5-10.1C45 10.4 39.2 4 32 4zM26.5 18.2c-1.4 0-2.5-1.3-2.5-2.8s1.1-2.8 2.5-2.8 2.5 1.3 2.5 2.8-1.1 2.8-2.5 2.8zm11 0c-1.4 0-2.5-1.3-2.5-2.8s1.1-2.8 2.5-2.8 2.5 1.3 2.5 2.8-1.1 2.8-2.5 2.8zM24 43.8c.8-4.1 2.4-7 4.1-8.2 1.1.8 2.4 1.2 3.9 1.2s2.8-.4 3.9-1.2c1.7 1.2 3.3 4.1 4.1 8.2-1.1 1-3.8 2-8 2s-6.9-1-8-2z"/></svg>';
    }

    return '';
}

$platforms=array(
    array(
        'key'=>'mac',
        'title'=>'Mac',
        'description'=>'Download the VCSEL App installer for macOS.',
        'extensions'=>array('dmg')
    ),
    array(
        'key'=>'windows',
        'title'=>'Windows',
        'description'=>'Windows installer will appear here when uploaded.',
        'extensions'=>array('exe','msi','zip')
    ),
    array(
        'key'=>'linux',
        'title'=>'Linux',
        'description'=>'Linux package will appear here when uploaded.',
        'extensions'=>array('AppImage','deb','rpm','tar.gz','zip')
    )
);

require_once('common/includes/header_inner.inc.php');
?>
<div class="app_download_page">
    <div class="app_download_header">
        <h2>VCSEL App</h2>
        <p>Select your operating system to download the production app.</p>
    </div>

    <div class="app_download_grid">
        <?php
        foreach($platforms as $platform){
            $download=vcsel_app_download($platform['key'],$platform['extensions']);
                echo '<div class="app_download_card">';
                    echo '<div class="app_download_icon platform_' . $platform['key'] . '">' . vcsel_app_logo($platform['key']) . '</div>';
                    echo '<h3>' . $platform['title'] . '</h3>';
                    if($download){
                        echo '<a href="' . $download['url'] . '" download>Download</a>';
                    }else{
                        echo '<button type="button" disabled>Unavailable</button>';
                    }
                echo '</div>';
        }
        ?>
    </div>
</div>
<?php
require_once('common/includes/footer_inner.inc.php');
?>
