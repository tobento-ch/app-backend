<?php
use function Tobento\Service\Acl\can;
?>
<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>">
    
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        
        <title><?= $view->esc($message) ?></title>
        
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
    </head>
    
    <body<?= $view->tagAttributes('body')->add('class', 'page')->render() ?>>
        <?php if (can('backend')) { ?>
            <?= $view->render('inc/header') ?>
            <?= $view->render('inc/nav') ?>
        <?php } ?>

        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="text-xl">
                <?= $view->esc($message) ?>
            </h1>

        </main>
        
        <?php if (can('backend')) { ?>
            <?= $view->render('inc/footer') ?>
        <?php } ?>
    </body>
</html>