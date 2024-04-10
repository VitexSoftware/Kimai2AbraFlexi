<?php

/**
 * KimaiToAbraFlexi - AppInit.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2022 Vitex Software
 */

namespace Kimai2AbraFlexi;

require_once '../vendor/autoload.php';

define('APP_NAME','Kimai2AbraFlexi');
if (file_exists(dirname(__DIR__) . '/.env')) {
    \Ease\Shared::instanced()->loadConfig(dirname(__DIR__) . '/.env', true);
}

new \Ease\Locale('cs_CZ', '../i18n', 'kimai-to-abraflexi');

$cfgKeys = [
    'ABRAFLEXI_URL',
    'ABRAFLEXI_LOGIN',
    'ABRAFLEXI_PASSWORD',
    'ABRAFLEXI_COMPANY',
    'ABRAFLEXI_CUSTOMER',
    'ABRAFLEXI_TYP_FAKTURY',
    'KIMAI_HOST',
    'KIMAI_USER',
    'KIMAI_TOKEN'
];

$configured = true;
foreach ($cfgKeys as $cfgKey) {
    if (empty(\Ease\Functions::cfg($cfgKey))) {
        fwrite(STDERR, 'Requied configuration ' . $cfgKey . " is not set." . PHP_EOL);
        $configured = false;
    }
}
if ($configured === false) {
    exit(1);
}

$engine = new Importer();
$engine->import();

if (\Ease\Functions::cfg('INVOICE_DOWNLOAD') && \Ease\Functions::cfg('REPORTS_DIR')) {
    $engine->addStatusMessage(sprintf(_('Invoice saved as %s'), $engine->downloadInFormat('pdf', \Ease\Functions::cfg('REPORTS_DIR'))));
    $engine->addStatusMessage(sprintf(_('Invoice saved as %s'), $engine->downloadInFormat('isdocx', \Ease\Functions::cfg('REPORTS_DIR'))));
}
