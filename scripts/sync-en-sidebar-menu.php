<?php
/**
 * English translations for sidebar, dashboard menus, and common UI labels.
 */
$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$content = file_get_contents($enPath);

$map = [
    // Auth / header / common
    'kirundi' => 'Kirundi',
    'login' => 'Login',
    'logout' => 'Logout',
    'users' => 'Users',
    'suppliers' => 'Suppliers',
    'setting' => 'Settings',
    'roles' => 'Roles',
    'permissions' => 'Permissions',
    'stock_report' => 'Stock report',
    'Note de Credit' => 'Credit note',

    // Master data
    'unités de mesure matières premières' => 'Raw material units of measure',
    'unités de mesure materiel' => 'Material units of measure',
    'Categorie matières premières' => 'Raw material category',
    'Categorie Materiel' => 'Material category',
    'matières premières' => 'Raw materials',
    'Materiel' => 'Material',
    'Materiels' => 'Materials',
    'Items' => 'Items',

    // Stock stores
    'Grand Stock des matières premières' => 'Main raw materials store',
    'Stock Intermediaire des matières premières' => 'Intermediate raw materials store',
    'Petit Stock des matières premières' => 'Small raw materials store',
    'finished_goods_store_menu' => 'Finished goods store',
    'finished_goods_sm_store_menu' => 'Small finished goods store',
    'finished_goods_inventory_menu' => 'Finished goods inventory',
    'finished_goods_sm_inventory_menu' => 'Small finished goods inventory',
    'Grand Stock des Materiels' => 'Main materials store',
    'Stock Intermediaire des Materiels' => 'Intermediate materials store',
    'Petit Stock des Materiels' => 'Small materials store',
    'stock des marchandises' => 'Goods store',
    'Stock des produits finis' => 'Finished goods store',
    'Petit Stock des produits finis' => 'Small finished goods store',

    // Production
    'Production' => 'Production',
    'Plan de production' => 'Production plan',
    'Requisition' => 'Requisition',
    'Transfer' => 'Transfer',
    'Production Order' => 'Production order',

    // Purchases
    'Demande Achat matières premières' => 'Raw material purchase request',
    'Demande Achat Materiel' => 'Material purchase request',
    'Commande matières premières' => 'Raw material supplier order',
    'Commande Materiel' => 'Material supplier order',
    'Reception matières premières' => 'Raw material receipt',
    'Reception Materiel' => 'Material receipt',

    // Sales
    'Proforma Invoice' => 'Proforma invoice',
    'Bons de commande' => 'Sales orders',
    'Delivery Note' => 'Delivery note',
    'Invoices' => 'Invoices',
    'Remboursement Caution' => 'Deposit refund',
    'Credits' => 'Credits',
    'Recouvrement' => 'Debt collection',

    // Stock in/out
    'Entree matières premières' => 'Raw material stock in',
    'Entree des produits finis' => 'Finished goods stock in',
    'Entree Materiels' => 'Material stock in',
    'Entree des marchandises' => 'Goods stock in',
    'goods_import_menu' => 'Goods imports',
    'Sortie matières premières' => 'Raw material stock out',
    'Sortie des produits finis' => 'Finished goods stock out',
    'Sortie des marchandises' => 'Goods stock out',
    'Sortie Materiels' => 'Material stock out',

    // Stock reports
    'Grand stock matières premières ' => 'Main raw materials stock report',
    'Intermidiaire stock matières premières ' => 'Intermediate raw materials stock report',
    'Petit stock matières premières ' => 'Small raw materials stock report',
    'Grand stock materiel ' => 'Main materials stock report',
    'Intermediaire stock materiel ' => 'Intermediate materials stock report',
    'Petit stock materiel ' => 'Small materials stock report',

    // HR
    'Employes' => 'Employees',
    'Entreprises' => 'Companies',
    'Département' => 'Department',
    'Services' => 'Services',
    'Fonctions' => 'Job titles',
    'Grades' => 'Grades',
    'Banques' => 'Banks',
    'Employés' => 'Employees',
    'Stagiaires' => 'Interns',
    'Ecoles' => 'Schools',
    'Filieres' => 'Study programs',
    'Conges' => 'Leave',
    'Type Congé' => 'Leave type',
    'Congés' => 'Leave requests',
    'Congé Payé' => 'Paid leave',
    'Time Table' => 'Time table',
    'Attendance Transactions' => 'Attendance transactions',
    'first_in_last_out' => 'First in, last out',
    'Bulletin de paie' => 'Payslip',
    'Avance sur salaire' => 'Salary advance',
    'Journal de paie' => 'Payroll journal',
    'Réglages' => 'Settings',
    'Les Journaux' => 'Journals',
    'Journal Paie' => 'Payroll journal',
    'Journal Cotisations' => 'Contributions journal',
    'Journal IRE' => 'Income tax journal',

    // Accounting
    'Accounting' => 'Accounting',
    'Chart Accounts' => 'Chart of accounts',
    'Advance Payment' => 'Advance payment',
    'Fund Transfer' => 'Fund transfer',
    'Currency' => 'Currency',
    'Expense Category' => 'Expense category',
    'Expenses' => 'Expenses',
    'Cash Out Flow Request' => 'Cash outflow request',
    'Cash Out Flow' => 'Cash outflow',
    'Return Fund' => 'Return of funds',
    'Journal Entry' => 'Journal entry',
    'Accounts Balance' => 'Accounts balance',
    'Active Balance Sheet' => 'Assets balance sheet',
    'Passive Balance Sheet' => 'Liabilities balance sheet',
    'Profit and Loss Statement' => 'Profit and loss statement',
    'Account Setting' => 'Account settings',

    // Dashboard (reinforce)
    'profitability_dashboard' => 'Profitability dashboard',
    'fiscal_year' => 'Fiscal year',
    'company_profitability' => 'Company profitability',
    'products_accounts_7' => 'Revenue (accounts 7*)',
    'charges_accounts_6' => 'Expenses (accounts 6*)',
    'profit' => 'Profit',
    'sales' => 'Sales',
    'cash' => 'Cash',
    'credit' => 'Credit',
    'invoice_count' => 'Number of invoices',
    'total_amount' => 'Total amount',
    'purchases_by_category' => 'Purchases by category',
    'raw_materials' => 'Raw materials',
    'materials' => 'Materials',
    'paid' => 'Paid',
    'unpaid' => 'Unpaid',
    'dashboard_procurement_supplies' => 'Supplies procurement process',
    'dashboard_procurement_raw_materials' => 'Raw materials procurement process',
    'dashboard_step_purchase_request' => 'Purchase request',
    'dashboard_step_purchase_order' => 'Supplier order',
    'dashboard_step_purchase_receipt' => 'Receipt',
    'dashboard_unapproved_pending' => 'Pending approval',
    'dashboard_payment_process_title' => 'Payment process',
    'dashboard_payment_step_cash_out_request' => 'Cash outflow request',
    'dashboard_payment_step_cash_out' => 'Cash outflow',
    'dashboard_payment_step_return_fund' => 'Return of funds',
    'dashboard_payment_link_help' => 'For cash purchases, start payment from the supplier order. For credit purchases, start payment from the receipt after goods are received.',
    'dashboard_payment_cash_via_order_supplies' => 'Cash payment via supplies order',
    'dashboard_payment_cash_via_order_raw_materials' => 'Cash payment via raw materials order',
    'dashboard_payment_credit_via_reception_supplies' => 'Credit payment via supplies receipt',
    'dashboard_payment_credit_via_reception_raw_materials' => 'Credit payment via raw materials receipt',
    'dashboard_production_process_title' => 'Production process',
    'dashboard_sales_process_title' => 'Sales process',
    'dashboard_invoice_in_progress' => 'In progress (not yet validated)',
    'app_brand' => 'EGOTEK',
    'app_header_product' => 'EGOTEK SOFT',
    'basic_file' => 'Master data',
    'home' => 'Home',
    'stock' => 'Stock',
    'requisition' => 'Requisition',
    'purchases' => 'Purchases',
    'Exchange Rate' => 'Exchange rate',
];

$updated = 0;
$added = 0;
foreach ($map as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", $v);
    $needle = "\t'{$ek}' =>";
    if (strpos($content, $needle) !== false) {
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, $needle) === 0) {
                $lines[$i] = "\t'{$ek}' => '{$ev}',";
                $updated++;
                break;
            }
        }
        $content = implode("\n", $lines);
    } else {
        $content = preg_replace('/\];\s*\?>\s*$/s', "\t'{$ek}' => '{$ev}',\n];\n\n\n?>", $content, 1);
        $added++;
    }
}

file_put_contents($enPath, $content);
echo "EN sidebar/menu: updated $updated, added $added\n";
