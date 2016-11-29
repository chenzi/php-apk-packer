# php-apk-packer
Using PHP to create APK channel package

# Exp

- Include ApkPacker.php file

    include  "php-apk-packer/ApkPacker.php";

- Create ApkPacker Object

    $apkPacker = new \ApkPacker\ApkPacker();

- Call packerSingleApk function to write channelName into apk

    $apkPacker->packerSingleApk('source.apk','channelName',"target.apk");






