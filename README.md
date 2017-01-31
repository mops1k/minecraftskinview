SkinView
========
Minecraft skin preview converter (support HD skins and capes)

## Installation
```bash
composer require mops1k/minecraftskinview
```

## Usage example
```php
use Minecraft\SkinView;

require_once './vendor/autoload.php';

$skinView = new SkinView();
$skinView
    ->setWaySkin('img_for_test/hd_skin.png')
    ->setWayCloak('img_for_test/hd_cape.png')
    ->setSaveSkin('save/full_hd_all.png')
    ->setSaveHead('save/head.png')
;

$skinView
    ->saveHead()
;

$skinView
    ->savePreview()
;

```
