<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $view->etrans('Dashboard') ?></title>
        <meta name="description" content="<?= $view->etrans('Dashboard') ?>">
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        <?php
        $view->asset('assets/card/card.js')->attr('type', 'module');
        ?>
    </head>
    <body<?= $view->tagAttributes('body')->add('class', 'page')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>

        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="title text-xl"><?= $view->etrans('Dashboard') ?></h1>
            
            <div class="cards cards-large fit mt-s" data-min-width="700px">
                <?php foreach($cards as $card) { ?>
                    <?= $card->render() ?>
                <?php } ?>
            </div>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>