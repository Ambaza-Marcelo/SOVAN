<?php
$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$content = file_get_contents($enPath);
$map = [
    'Scheduling' => 'Scheduling',
    'Planned Start Time' => 'Planned start time',
    'Planned Finish Time' => 'Planned finish time',
    'Product & Quantity (Finished Goods)' => 'Product & quantity (finished goods)',
    'Item Code / Name' => 'Item code / name',
    'Materials (Raw Materials)' => 'Materials (raw materials)',
    '(Auto-loaded from ItemDetail when finished goods are selected)' => '(Auto-loaded from ItemDetail when finished goods are selected)',
    'Add More Raw Material' => 'Add more raw material',
    'Costing' => 'Costing',
    'Estimated Labor Cost' => 'Estimated labor cost',
    'Estimated Overhead Cost' => 'Estimated overhead cost',
    'Total Estimated Cost' => 'Total estimated cost',
    '(Calculated automatically)' => '(Calculated automatically)',
    'Requisition' => 'Requisition',
    'Transfer' => 'Transfer',
    'Invoices' => 'Invoices',
    'Create setting' => 'Create setting',
    'Type Contribuable' => 'Taxpayer type',
    'Assujetti à la TVA' => 'Subject to VAT',
    'Assujetti à la taxe de conso.' => 'Subject to consumption tax',
    'Dashboard' => 'Dashboard',
    'All Users' => 'All users',
    'Create User' => 'Create user',
    'Create New Role' => 'Create new role',
    'User Name' => 'User name',
    'User Email' => 'User email',
    'Password' => 'Password',
    'Confirm Password' => 'Confirm password',
    'Assign Roles' => 'Assign roles',
    'Save User' => 'Save user',
    'Users' => 'Users',
    'Sl' => 'No.',
    'Name' => 'Name',
    'Email' => 'Email',
    'Roles' => 'Roles',
    'Role Name' => 'Role name',
    'Permissions' => 'Permissions',
    'All' => 'All',
    'Save Role' => 'Save role',
    'Edit Role' => 'Edit role',
    'Update Role' => 'Update role',
    'Order Details' => 'Order details',
    'Edit Order' => 'Edit order',
    'Production Order Details:' => 'Production order details:',
    'Edit Production Order:' => 'Edit production order:',
    'Products & Quantity' => 'Products & quantity',
    'Routing' => 'Routing',
];
foreach ($map as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", $v);
    $needle = "\t'{$ek}' =>";
    if (strpos($content, $needle) !== false) {
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, $needle) === 0) {
                $lines[$i] = "\t'{$ek}' => '{$ev}',";
                break;
            }
        }
        $content = implode("\n", $lines);
    } else {
        $content = preg_replace('/\];\s*\?>\s*$/s', "\t'{$ek}' => '{$ev}',\n];\n\n\n?>", $content, 1);
    }
}
file_put_contents($enPath, $content);
echo 'Production/Dashboard/Settings EN: ' . count($map) . " keys\n";
