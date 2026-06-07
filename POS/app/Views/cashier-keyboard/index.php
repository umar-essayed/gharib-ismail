<?php
$title = 'إعدادات كيبورد CNR Cashier';
\App\Core\View::partial('cashier-keyboard/partials/manager', [
    'shortcuts' => $shortcuts,
    'actionTypes' => $actionTypes,
    'keyboardHeading' => 'كيبورد CNR Cashier',
    'keyboardSubheading' => 'تخصيص أزرار الكيبورد مع شاشة البيع والبرنامج.',
]);
?>
