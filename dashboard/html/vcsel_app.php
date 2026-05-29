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
                echo '<div class="app_download_icon">' . strtoupper(substr($platform['title'],0,2)) . '</div>';
                echo '<h3>' . $platform['title'] . '</h3>';
                echo '<p>' . $platform['description'] . '</p>';
                if($download){
                    echo '<span>' . $download['name'] . (!empty($download['size'])?' &bull; ' . vcsel_app_size($download['size']):'') . '</span>';
                    echo '<a href="' . $download['url'] . '" download>Download</a>';
                }else{
                    echo '<span>No installer uploaded yet</span>';
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
