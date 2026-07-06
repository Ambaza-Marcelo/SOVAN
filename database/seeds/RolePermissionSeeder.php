<?php
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Class RolePermissionSeeder.
 *
 * @see https://spatie.be/docs/laravel-permission/v5/basic-usage/multiple-guards
 *
 * @package App\Database\Seeds
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        // Permission List as array
        $permissions = [

            [
                'group_name' => 'dashboard',
                'permissions' => [
                    'dashboard.view',
                    'dashboard.edit',
                ]
            ],
            [
                'group_name' => 'admin',
                'permissions' => [
                    // admin Permissions
                    'admin.create',
                    'admin.view',
                    'admin.edit',
                    'admin.delete',
                ]
            ],
            [
                'group_name' => 'role',
                'permissions' => [
                    // role Permissions
                    'role.create',
                    'role.view',
                    'role.edit',
                    'role.delete',
                ]
            ],
            [
                'group_name' => 'material',
                'permissions' => [
                    // material Permissions
                    'material.create',
                    'material.view',
                    'material.edit',
                    'material.delete',
                ]
            ],
            [
                'group_name' => 'item',
                'permissions' => [
                    // item Permissions
                    'item.create',
                    'item.view',
                    'item.edit',
                    'item.delete',
                ]
            ],

            [
                'group_name' => 'raw_material',
                'permissions' => [
                    // raw_material Permissions
                    'raw_material.create',
                    'raw_material.view',
                    'raw_material.edit',
                    'raw_material.delete',
                ]
            ],
            [
                'group_name' => 'raw_material_category',
                'permissions' => [
                    // raw_material_category Permissions
                    'raw_material_category.create',
                    'raw_material_category.view',
                    'raw_material_category.edit',
                    'raw_material_category.delete',
                ]
            ],
            [
                'group_name' => 'material_category',
                'permissions' => [
                    // material_category Permissions
                    'material_category.create',
                    'material_category.view',
                    'material_category.edit',
                    'material_category.delete',
                ]
            ],
            [
                'group_name' => 'finished_product_stock',
                'permissions' => [
                    // finished_product_stock Permissions
                    'finished_product_stock.create',
                    'finished_product_stock.view',
                    'finished_product_stock.edit',
                    'finished_product_stock.delete'
                ]
            ],
            [
                'group_name' => 'finished_product_sm_stock',
                'permissions' => [
                    // finished_product_sm_stock Permissions
                    'finished_product_sm_stock.create',
                    'finished_product_sm_stock.view',
                    'finished_product_sm_stock.edit',
                    'finished_product_sm_stock.delete'
                ]
            ],
            [
                'group_name' => 'goods_stock',
                'permissions' => [
                    // goods_stock Permissions
                    'goods_stock.create',
                    'goods_stock.view',
                    'goods_stock.edit',
                    'goods_stock.delete'
                ]
            ],
            [
                'group_name' => 'raw_material_big_store',
                'permissions' => [
                    // raw_material_big_store Permissions
                    'raw_material_big_store.create',
                    'raw_material_big_store.view',
                    'raw_material_big_store.edit',
                    'raw_material_big_store.delete'
                ]
            ],
            [
                'group_name' => 'raw_material_medium_store',
                'permissions' => [
                    // raw_material_medium_store Permissions
                    'raw_material_medium_store.create',
                    'raw_material_medium_store.view',
                    'raw_material_medium_store.edit',
                    'raw_material_medium_store.delete'
                ]
            ],
            [
                'group_name' => 'raw_material_small_store',
                'permissions' => [
                    // raw_material_small_store Permissions
                    'raw_material_small_store.create',
                    'raw_material_small_store.view',
                    'raw_material_small_store.edit',
                    'raw_material_small_store.delete'
                ]
            ],
            [
                'group_name' => 'material_big_store',
                'permissions' => [
                    // material_big_store Permissions
                    'material_big_store.create',
                    'material_big_store.view',
                    'material_big_store.edit',
                    'material_big_store.delete',
                ]
            ],
            [
                'group_name' => 'material_small_store',
                'permissions' => [
                    // material_small_store Permissions
                    'material_small_store.create',
                    'material_small_store.view',
                    'material_small_store.edit',
                    'material_small_store.delete',
                ]
            ],
            [
                'group_name' => 'material_medium_store',
                'permissions' => [
                    // material_medium_store Permissions
                    'material_medium_store.create',
                    'material_medium_store.view',
                    'material_medium_store.edit',
                    'material_medium_store.delete',
                ]
            ],
            [
                'group_name' => 'raw_material_reception',
                'permissions' => [
                    // raw_material_reception Permissions
                    'raw_material_reception.create',
                    'raw_material_reception.view',
                    'raw_material_reception.edit',
                    'raw_material_reception.show',
                    'raw_material_reception.delete',
                    'raw_material_reception.validate',
                    'raw_material_reception.confirm',
                    'raw_material_reception.approuve',
                    'raw_material_reception.reset',
                    'raw_material_reception.reject',
                ]
            ],
            [
                'group_name' => 'material_reception',
                'permissions' => [
                    // material_reception Permissions
                    'material_reception.create',
                    'material_reception.view',
                    'material_reception.edit',
                    'material_reception.show',
                    'material_reception.delete',
                    'material_reception.validate',
                    'material_reception.confirm',
                    'material_reception.approuve',
                    'material_reception.reset',
                    'material_reception.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_requisition',
                'permissions' => [
                    // raw_material_requisition Permissions
                    'raw_material_requisition.create',
                    'raw_material_requisition.view',
                    'raw_material_requisition.edit',
                    'raw_material_requisition.show',
                    'raw_material_requisition.delete',
                    'raw_material_requisition.validate',
                    'raw_material_requisition.confirm',
                    'raw_material_requisition.approuve',
                    'raw_material_requisition.reset',
                    'raw_material_requisition.reject',
                ]
            ],
            [
                'group_name' => 'material_requisition',
                'permissions' => [
                    // material_requisition Permissions
                    'material_requisition.create',
                    'material_requisition.view',
                    'material_requisition.edit',
                    'material_requisition.show',
                    'material_requisition.delete',
                    'material_requisition.validate',
                    'material_requisition.confirm',
                    'material_requisition.approuve',
                    'material_requisition.reset',
                    'material_requisition.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_order_client',
                'permissions' => [
                    // raw_material_order_client Permissions
                    'raw_material_order_client.create',
                    'raw_material_order_client.view',
                    'raw_material_order_client.edit',
                    'raw_material_order_client.show',
                    'raw_material_order_client.delete',
                    'raw_material_order_client.validate',
                    'raw_material_order_client.confirm',
                    'raw_material_order_client.approuve',
                    'raw_material_order_client.reset',
                    'raw_material_order_client.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_transfer',
                'permissions' => [
                    // raw_material_transfer Permissions
                    'raw_material_transfer.create',
                    'raw_material_transfer.view',
                    'raw_material_transfer.edit',
                    'raw_material_transfer.show',
                    'raw_material_transfer.delete',
                    'raw_material_transfer.validate',
                    'raw_material_transfer.confirm',
                    'raw_material_transfer.approuve',
                    'raw_material_transfer.reset',
                    'raw_material_transfer.reject',
                    'raw_material_transfer.validatePortion',
                    'raw_material_transfer.portion',
                ]
            ],
            [
                'group_name' => 'material_transfer',
                'permissions' => [
                    // material_transfer Permissions
                    'material_transfer.create',
                    'material_transfer.view',
                    'material_transfer.edit',
                    'material_transfer.show',
                    'material_transfer.delete',
                    'material_transfer.validate',
                    'material_transfer.confirm',
                    'material_transfer.approuve',
                    'material_transfer.reset',
                    'material_transfer.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_purchase',
                'permissions' => [
                    // raw_material_purchase Permissions
                    'raw_material_purchase.create',
                    'raw_material_purchase.view',
                    'raw_material_purchase.edit',
                    'raw_material_purchase.show',
                    'raw_material_purchase.delete',
                    'raw_material_purchase.validate',
                    'raw_material_purchase.confirm',
                    'raw_material_purchase.approuve',
                    'raw_material_purchase.reset',
                    'raw_material_purchase.reject',
                ]
            ],

            [
                'group_name' => 'material_purchase',
                'permissions' => [
                    // material_purchase Permissions
                    'material_purchase.create',
                    'material_purchase.view',
                    'material_purchase.edit',
                    'material_purchase.show',
                    'material_purchase.delete',
                    'material_purchase.validate',
                    'material_purchase.confirm',
                    'material_purchase.approuve',
                    'material_purchase.reset',
                    'material_purchase.reject',
                ]
            ],

            [
                'group_name' => 'raw_material_stockin',
                'permissions' => [
                    // raw_material_stockin Permissions
                    'raw_material_stockin.create',
                    'raw_material_stockin.view',
                    'raw_material_stockin.edit',
                    'raw_material_stockin.show',
                    'raw_material_stockin.delete',
                    'raw_material_stockin.validate',
                    'raw_material_stockin.confirm',
                    'raw_material_stockin.approuve',
                    'raw_material_stockin.reset',
                    'raw_material_stockin.reject',
                ]
            ],
            [
                'group_name' => 'finished_goods_stockin',
                'permissions' => [
                    // finished_goods_stockin Permissions
                    'finished_goods_stockin.create',
                    'finished_goods_stockin.view',
                    'finished_goods_stockin.edit',
                    'finished_goods_stockin.show',
                    'finished_goods_stockin.delete',
                    'finished_goods_stockin.validate',
                    'finished_goods_stockin.confirm',
                    'finished_goods_stockin.approuve',
                    'finished_goods_stockin.reset',
                    'finished_goods_stockin.reject',
                ]
            ],
            [
                'group_name' => 'finished_goods_stockout',
                'permissions' => [
                    // finished_goods_stockout Permissions
                    'finished_goods_stockout.create',
                    'finished_goods_stockout.view',
                    'finished_goods_stockout.edit',
                    'finished_goods_stockout.show',
                    'finished_goods_stockout.delete',
                    'finished_goods_stockout.validate',
                    'finished_goods_stockout.confirm',
                    'finished_goods_stockout.approuve',
                    'finished_goods_stockout.reset',
                    'finished_goods_stockout.reject',
                ]
            ],
            [
                'group_name' => 'goods_stockin',
                'permissions' => [
                    // goods_stockin Permissions
                    'goods_stockin.create',
                    'goods_stockin.view',
                    'goods_stockin.edit',
                    'goods_stockin.show',
                    'goods_stockin.delete',
                    'goods_stockin.validate',
                    'goods_stockin.confirm',
                    'goods_stockin.approuve',
                    'goods_stockin.reset',
                    'goods_stockin.reject',
                ]
            ],
            [
                'group_name' => 'goods_import',
                'permissions' => [
                    'goods_import.create',
                    'goods_import.view',
                    'goods_import.edit',
                    'goods_import.show',
                    'goods_import.delete',
                    'goods_import.validate',
                    'goods_import.confirm',
                    'goods_import.approuve',
                    'goods_import.reset',
                    'goods_import.reject',
                ]
            ],
            [
                'group_name' => 'goods_stockout',
                'permissions' => [
                    // goods_stockout Permissions
                    'goods_stockout.create',
                    'goods_stockout.view',
                    'goods_stockout.edit',
                    'goods_stockout.show',
                    'goods_stockout.delete',
                    'goods_stockout.validate',
                    'goods_stockout.confirm',
                    'goods_stockout.approuve',
                    'goods_stockout.reset',
                    'goods_stockout.reject',
                ]
            ],
            [
                'group_name' => 'material_stockin',
                'permissions' => [
                    // material_stockin Permissions
                    'material_stockin.create',
                    'material_stockin.view',
                    'material_stockin.edit',
                    'material_stockin.show',
                    'material_stockin.delete',
                    'material_stockin.validate',
                    'material_stockin.confirm',
                    'material_stockin.approuve',
                    'material_stockin.reset',
                    'material_stockin.reject',
                ]
            ],
            [
                'group_name' => 'material_stockout',
                'permissions' => [
                    // material_stockout Permissions
                    'material_stockout.create',
                    'material_stockout.view',
                    'material_stockout.edit',
                    'material_stockout.show',
                    'material_stockout.delete',
                    'material_stockout.validate',
                    'material_stockout.confirm',
                    'material_stockout.approuve',
                    'material_stockout.reset',
                    'material_stockout.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_stockout',
                'permissions' => [
                    // raw_material_stockout Permissions
                    'raw_material_stockout.create',
                    'raw_material_stockout.view',
                    'raw_material_stockout.edit',
                    'raw_material_stockout.show',
                    'raw_material_stockout.delete',
                    'raw_material_stockout.validate',
                    'raw_material_stockout.confirm',
                    'raw_material_stockout.approuve',
                    'raw_material_stockout.reset',
                    'raw_material_stockout.reject'
                ]
            ],

            [
                'group_name' => 'supplier',
                'permissions' => [
                    // supplier Permissions
                    'supplier.create',
                    'supplier.view',
                    'supplier.edit',
                    'supplier.delete',
                ]
            ],
            [
                'group_name' => 'client',
                'permissions' => [
                    // client Permissions
                    'client.create',
                    'client.view',
                    'client.edit',
                    'client.delete',
                ]
            ],
             [
                'group_name' => 'setting',
                'permissions' => [
                    // setting Permissions
                    'setting.create',
                    'setting.view',
                    'setting.edit',
                    'setting.delete',
                ]
            ],
            [
                'group_name' => 'raw_material_supplier_order',
                'permissions' => [
                    // raw_material_supplier_order Permissions
                    'raw_material_supplier_order.create',
                    'raw_material_supplier_order.view',
                    'raw_material_supplier_order.edit',
                    'raw_material_supplier_order.delete',
                    'raw_material_supplier_order.validate',
                    'raw_material_supplier_order.confirm',
                    'raw_material_supplier_order.approuve',
                    'raw_material_supplier_order.reset',
                    'raw_material_supplier_order.reject',
                ]
            ],
            [
                'group_name' => 'material_supplier_order',
                'permissions' => [
                    // material_supplier_order Permissions
                    'material_supplier_order.create',
                    'material_supplier_order.view',
                    'material_supplier_order.edit',
                    'material_supplier_order.delete',
                    'material_supplier_order.validate',
                    'material_supplier_order.confirm',
                    'material_supplier_order.approuve',
                    'material_supplier_order.reset',
                    'material_supplier_order.reject',
                ]
            ],
            [
                'group_name' => 'fiches',
                'permissions' => [
                    // fiches Permissions
                    'facture.imprimer',
                    'facture.reimprimer',
                    'document.imprimer',
                    'document.reimprimer',
                ]
            ],
            [
                'group_name' => 'raw_material_medium_inventory',
                'permissions' => [
                    // raw_material_medium_inventory Permissions
                    'raw_material_medium_inventory.view',
                    'raw_material_medium_inventory.create',
                    'raw_material_medium_inventory.edit',
                    'raw_material_medium_inventory.show',
                    'raw_material_medium_inventory.delete',
                    'raw_material_medium_inventory.validate',
                    'raw_material_medium_inventory.reset',
                    'raw_material_medium_inventory.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_big_inventory',
                'permissions' => [
                    // raw_material_big_inventory Permissions
                    'raw_material_big_inventory.view',
                    'raw_material_big_inventory.create',
                    'raw_material_big_inventory.edit',
                    'raw_material_big_inventory.show',
                    'raw_material_big_inventory.delete',
                    'raw_material_big_inventory.validate',
                    'raw_material_big_inventory.reset',
                    'raw_material_big_inventory.reject',
                ]
            ],
            [
                'group_name' => 'raw_material_small_inventory',
                'permissions' => [
                    // raw_material_small_inventory Permissions
                    'raw_material_small_inventory.view',
                    'raw_material_small_inventory.create',
                    'raw_material_small_inventory.edit',
                    'raw_material_small_inventory.show',
                    'raw_material_small_inventory.delete',
                    'raw_material_small_inventory.validate',
                    'raw_material_small_inventory.reset',
                    'raw_material_small_inventory.reject',
                ]
            ],
            [
                'group_name' => 'finished_goods_inventory',
                'permissions' => [
                    // finished_goods_inventory Permissions
                    'finished_goods_inventory.view',
                    'finished_goods_inventory.create',
                    'finished_goods_inventory.edit',
                    'finished_goods_inventory.show',
                    'finished_goods_inventory.delete',
                    'finished_goods_inventory.validate',
                    'finished_goods_inventory.reset',
                    'finished_goods_inventory.reject',
                ]
            ],
            [
                'group_name' => 'finished_goods_sm_store_inventory',
                'permissions' => [
                    // finished_goods_sm_store_inventory Permissions
                    'finished_goods_sm_store_inventory.view',
                    'finished_goods_sm_store_inventory.create',
                    'finished_goods_sm_store_inventory.edit',
                    'finished_goods_sm_store_inventory.show',
                    'finished_goods_sm_store_inventory.delete',
                    'finished_goods_sm_store_inventory.validate',
                    'finished_goods_sm_store_inventory.reset',
                    'finished_goods_sm_store_inventory.reject',
                ]
            ],
            [
                'group_name' => 'goods_inventory',
                'permissions' => [
                    // goods_inventory Permissions
                    'goods_inventory.view',
                    'goods_inventory.create',
                    'goods_inventory.edit',
                    'goods_inventory.show',
                    'goods_inventory.delete',
                    'goods_inventory.validate',
                    'goods_inventory.reset',
                    'goods_inventory.reject',
                ]
            ],
            [
                'group_name' => 'material_medium_inventory',
                'permissions' => [
                    // material_medium_inventory Permissions
                    'material_medium_inventory.view',
                    'material_medium_inventory.create',
                    'material_medium_inventory.edit',
                    'material_medium_inventory.show',
                    'material_medium_inventory.delete',
                    'material_medium_inventory.validate',
                    'material_medium_inventory.reset',
                    'material_medium_inventory.reject',
                ]
            ],
            [
                'group_name' => 'material_big_inventory',
                'permissions' => [
                    // material_big_inventory Permissions
                    'material_big_inventory.view',
                    'material_big_inventory.create',
                    'material_big_inventory.edit',
                    'material_big_inventory.show',
                    'material_big_inventory.delete',
                    'material_big_inventory.validate',
                    'material_big_inventory.reset',
                    'material_big_inventory.reject',
                ]
            ],
            [
                'group_name' => 'material_small_inventory',
                'permissions' => [
                    // material_small_inventory Permissions
                    'material_small_inventory.view',
                    'material_small_inventory.create',
                    'material_small_inventory.edit',
                    'material_small_inventory.show',
                    'material_small_inventory.delete',
                    'material_small_inventory.validate',
                    'material_small_inventory.reset',
                    'material_small_inventory.reject',
                ]
            ],
            [
                'group_name' => 'production_order',
                'permissions' => [
                    // production_order Permissions
                    'production_order.view',
                    'production_order.create',
                    'production_order.edit',
                    'production_order.show',
                    'production_order.delete',
                    'production_order.validate',
                    'production_order.confirm',
                    'production_order.approuve',
                    'production_order.reset',
                    'production_order.reject',
                ]
            ],
            [
                'group_name' => 'production_plan',
                'permissions' => [
                    // production_plan Permissions
                    'production_plan.view',
                    'production_plan.create',
                    'production_plan.edit',
                    'production_plan.show',
                    'production_plan.delete',
                    'production_plan.validate',
                    'production_plan.confirm',
                    'production_plan.approuve',
                    'production_plan.reset',
                    'production_plan.reject',
                ]
            ],
            [
                'group_name' => 'proforma_invoice',
                'permissions' => [
                    // proforma_invoice Permissions
                    'proforma_invoice.view',
                    'proforma_invoice.create',
                    'proforma_invoice.edit',
                    'proforma_invoice.show',
                    'proforma_invoice.delete',
                    'proforma_invoice.validate',
                    'proforma_invoice.confirm',
                    'proforma_invoice.approuve',
                    'proforma_invoice.reset',
                    'proforma_invoice.reject',
                ]
            ],
            [
                'group_name' => 'sales_order',
                'permissions' => [
                    // sales_order Permissions
                    'sales_order.view',
                    'sales_order.create',
                    'sales_order.edit',
                    'sales_order.show',
                    'sales_order.delete',
                    'sales_order.validate',
                    'sales_order.confirm',
                    'sales_order.approuve',
                    'sales_order.reset',
                    'sales_order.reject',
                ]
            ],
            [
                'group_name' => 'delivery_note',
                'permissions' => [
                    // delivery_note Permissions
                    'delivery_note.view',
                    'delivery_note.create',
                    'delivery_note.edit',
                    'delivery_note.show',
                    'delivery_note.delete',
                    'delivery_note.validate',
                    'delivery_note.confirm',
                    'delivery_note.approuve',
                    'delivery_note.reset',
                    'delivery_note.reject',
                ]
            ],
            [
                'group_name' => 'invoice',
                'permissions' => [
                    // invoice Permissions
                    'invoice.view',
                    'invoice.create',
                    'invoice.edit',
                    'invoice.show',
                    'invoice.delete',
                    'invoice.validate',
                    'invoice.confirm',
                    'invoice.approuve',
                    'invoice.reset',
                    'invoice.reject',
                ]
            ],
            [
                'group_name' => 'recouvrement',
                'permissions' => [
                    // recouvrement Permissions
                    'recouvrement.view',
                    'recouvrement.create',
                    'recouvrement.edit',
                    'recouvrement.show',
                    'recouvrement.validate',
                    'recouvrement.reset',
                    'recouvrement.reject',
                ]
            ],
            [
                'group_name' => 'note_credit',
                'permissions' => [
                    // note_credit Permissions
                    'note_credit.view',
                    'note_credit.create',
                    'note_credit.edit',
                    'note_credit.show',
                    'note_credit.validate',
                    'note_credit.confirm',
                    'note_credit.approuve',
                    'note_credit.reset',
                    'note_credit.reject',
                ]
            ],
            [
                'group_name' => 'remboursement_caution',
                'permissions' => [
                    // remboursement_caution Permissions
                    'remboursement_caution.view',
                    'remboursement_caution.create',
                    'remboursement_caution.edit',
                    'remboursement_caution.show',
                    'remboursement_caution.validate',
                    'remboursement_caution.confirm',
                    'remboursement_caution.approuve',
                    'remboursement_caution.reset',
                    'remboursement_caution.reject',
                ]
            ],
            [
                'group_name' => 'invoice_obr',
                'permissions' => [
                    // invoice_obr Permissions
                    'invoice_obr.view',
                    'invoice_obr.send',
                    'invoice_obr.reset',
                ]
            ],
            [
                'group_name' => 'raw_material_extra_big_report',
                'permissions' => [
                    // raw_material_extra_big_report Permissions
                    'raw_material_extra_big_report.view',
                    'raw_material_extra_big_report.edit',
                ]
            ],
            [
                'group_name' => 'raw_material_big_report',
                'permissions' => [
                    // raw_material_big_report Permissions
                    'raw_material_big_report.view',
                    'raw_material_big_report.edit',
                ]
            ],
            [
                'group_name' => 'raw_material_small_report',
                'permissions' => [
                    // raw_material_small_report Permissions
                    'raw_material_small_report.view',
                    'raw_material_small_report.edit',
                ]
            ],
            [
                'group_name' => 'material_extra_big_report',
                'permissions' => [
                    // material_extra_big_report Permissions
                    'material_extra_big_report.view',
                    'material_extra_big_report.edit',
                ]
            ],
            [
                'group_name' => 'material_big_report',
                'permissions' => [
                    // material_big_report Permissions
                    'material_big_report.view',
                    'material_big_report.edit',
                ]
            ],
            [
                'group_name' => 'material_small_report',
                'permissions' => [
                    // material_small_report Permissions
                    'material_small_report.view',
                    'material_small_report.edit',
                ]
            ],
            [
                'group_name' => 'finished_product_stock_report',
                'permissions' => [
                    // finished_product_stock_report Permissions
                    'finished_product_stock_report.view',
                    'finished_product_stock_report.edit',
                ]
            ],
            [
                'group_name' => 'finished_product_sm_stock_report',
                'permissions' => [
                    // finished_product_sm_stock_report Permissions
                    'finished_product_sm_stock_report.view',
                    'finished_product_sm_stock_report.edit',
                ]
            ],
            [
                'group_name' => 'goods_stock_report',
                'permissions' => [
                    // goods_stock_report Permissions
                    'goods_stock_report.view',
                    'goods_stock_report.edit',
                ]
            ],
            [
                'group_name' => 'hr_departement',
                'permissions' => [
                    // hr_departement Permissions
                    'hr_departement.create',
                    'hr_departement.view',
                    'hr_departement.edit',
                    'hr_departement.delete',
                ]
            ],
            [
                'group_name' => 'hr_service',
                'permissions' => [
                    // hr_service Permissions
                    'hr_service.create',
                    'hr_service.view',
                    'hr_service.edit',
                    'hr_service.delete',
                ]
            ],
            [
                'group_name' => 'hr_fonction',
                'permissions' => [
                    // hr_fonction Permissions
                    'hr_fonction.create',
                    'hr_fonction.view',
                    'hr_fonction.edit',
                    'hr_fonction.delete',
                ]
            ],

            [
                'group_name' => 'hr_grade',
                'permissions' => [
                    // hr_grade Permissions
                    'hr_grade.create',
                    'hr_grade.view',
                    'hr_grade.edit',
                    'hr_grade.delete',
                ]
            ],
            [
                'group_name' => 'hr_employe',
                'permissions' => [
                    // hr_employe Permissions
                    'hr_employe.create',
                    'hr_employe.view',
                    'hr_employe.edit',
                    'hr_employe.delete',
                ]
            ],
            [
                'group_name' => 'hr_banque',
                'permissions' => [
                    // hr_banque Permissions
                    'hr_banque.create',
                    'hr_banque.view',
                    'hr_banque.edit',
                    'hr_banque.delete',
                ]
            ],
            [
                'group_name' => 'hr_stagiaire',
                'permissions' => [
                    // hr_stagiaire Permissions
                    'hr_stagiaire.create',
                    'hr_stagiaire.view',
                    'hr_stagiaire.edit',
                    'hr_stagiaire.delete',
                ]
            ],
            [
                'group_name' => 'hr_ecole',
                'permissions' => [
                    // hr_ecole Permissions
                    'hr_ecole.create',
                    'hr_ecole.view',
                    'hr_ecole.edit',
                    'hr_ecole.delete',
                ]
            ],
            [
                'group_name' => 'hr_filiere',
                'permissions' => [
                    // hr_filiere Permissions
                    'hr_filiere.create',
                    'hr_filiere.view',
                    'hr_filiere.edit',
                    'hr_filiere.delete',
                ]
            ],
            [
                'group_name' => 'hr_cotation',
                'permissions' => [
                    // hr_cotation Permissions
                    'hr_cotation.create',
                    'hr_cotation.view',
                    'hr_cotation.edit',
                    'hr_cotation.delete',
                ]
            ],
            [
                'group_name' => 'hr_cotisation',
                'permissions' => [
                    // hr_cotisation Permissions
                    'hr_cotisation.create',
                    'hr_cotisation.view',
                    'hr_cotisation.edit',
                    'hr_cotisation.delete',
                ]
            ],
            [
                'group_name' => 'hr_indemnite',
                'permissions' => [
                    // hr_indemnite Permissions
                    'hr_indemnite.create',
                    'hr_indemnite.view',
                    'hr_indemnite.edit',
                    'hr_indemnite.delete',
                ]
            ],
            [
                'group_name' => 'hr_conge',
                'permissions' => [
                    // hr_conge Permissions
                    'hr_conge.create',
                    'hr_conge.view',
                    'hr_conge.edit',
                    'hr_conge.delete',
                ]
            ],
            [
                'group_name' => 'hr_conge_paye',
                'permissions' => [
                    // hr_conge_paye Permissions
                    'hr_conge_paye.create',
                    'hr_conge_paye.view',
                    'hr_conge_paye.edit',
                    'hr_conge_paye.delete',
                ]
            ],
            [
                'group_name' => 'hr_reglage',
                'permissions' => [
                    // hr_reglage Permissions
                    'hr_reglage.create',
                    'hr_reglage.view',
                    'hr_reglage.edit',
                    'hr_reglage.delete',
                ]
            ],
            [
                'group_name' => 'hr_note_interne',
                'permissions' => [
                    // hr_note_interne Permissions
                    'hr_note_interne.create',
                    'hr_note_interne.view',
                    'hr_note_interne.edit',
                    'hr_note_interne.delete',
                ]
            ],
            [
                'group_name' => 'hr_paiement',
                'permissions' => [
                    // hr_paiement Permissions
                    'hr_paiement.create',
                    'hr_paiement.view',
                    'hr_paiement.show',
                    'hr_paiement.edit',
                    'hr_paiement.print',
                    'hr_paiement.delete',
                ]
            ],
            [
                'group_name' => 'hr_journal_paie',
                'permissions' => [
                    // hr_journal_paie Permissions
                    'hr_journal_paie.create',
                    'hr_journal_paie.view',
                    'hr_journal_paie.cloturer',
                    'hr_journal_paie.show',
                    'hr_journal_paie.edit',
                    'hr_journal_paie.delete',
                ]
            ],
            [
                'group_name' => 'hr_journal_conge',
                'permissions' => [
                    // hr_journal_conge Permissions
                    'hr_journal_conge.create',
                    'hr_journal_conge.view',
                    'hr_journal_conge.show',
                    'hr_journal_conge.edit',
                    'hr_journal_conge.delete',
                ]
            ],
            [
                'group_name' => 'hr_journal_cotisation',
                'permissions' => [
                    // hr_journal_cotisation Permissions
                    'hr_journal_cotisation.create',
                    'hr_journal_cotisation.view',
                    'hr_journal_cotisation.show',
                    'hr_journal_cotisation.edit',
                    'hr_journal_cotisation.delete',
                ]
            ],
            [
                'group_name' => 'hr_absence',
                'permissions' => [
                    // hr_absence Permissions
                    'hr_absence.create',
                    'hr_absence.view',
                    'hr_absence.show',
                    'hr_absence.edit',
                    'hr_absence.delete',
                ]
            ],
            [
                'group_name' => 'hr_loan',
                'permissions' => [
                    // hr_loan Permissions
                    'hr_loan.create',
                    'hr_loan.view',
                    'hr_loan.show',
                    'hr_loan.edit',
                    'hr_loan.delete',
                ]
            ],
            [
                'group_name' => 'profile',
                'permissions' => [
                    // profile Permissions
                    'profile.view',
                    'profile.edit',
                ]
            ],
            [
                'group_name' => 'account_account',
                'permissions' => [
                    // account_account Permissions
                    'account_account.create',
                    'account_account.view',
                    'account_account.edit',
                    'account_account.show',
                    'account_account.delete',
                    'account_account.validate',
                    'account_account.confirm',
                    'account_account.approuve',
                    'account_account.reset',
                    'account_account.reject',
                ]
            ],
            [
                'group_name' => 'account_parent_account',
                'permissions' => [
                    // account_parent_account Permissions
                    'account_parent_account.create',
                    'account_parent_account.view',
                    'account_parent_account.edit',
                    'account_parent_account.show',
                    'account_parent_account.delete',
                    'account_parent_account.validate',
                    'account_parent_account.confirm',
                    'account_parent_account.approuve',
                    'account_parent_account.reset',
                    'account_parent_account.reject',
                ]
            ],

            [
                'group_name' => 'account_setting',
                'permissions' => [
                    // account_setting Permissions
                    'account_setting.create',
                    'account_setting.view',
                    'account_setting.edit',
                    'account_setting.show',
                    'account_setting.delete',
                    'account_setting.validate',
                    'account_setting.confirm',
                    'account_setting.approuve',
                    'account_setting.reset',
                    'account_setting.reject',
                ]
            ],

            [
                'group_name' => 'account_currency',
                'permissions' => [
                    // account_currency Permissions
                    'account_currency.create',
                    'account_currency.view',
                    'account_currency.edit',
                    'account_currency.show',
                    'account_currency.delete',
                    'account_currency.validate',
                    'account_currency.confirm',
                    'account_currency.approuve',
                    'account_currency.reset',
                    'account_currency.reject',
                ]
            ],

            [
                'group_name' => 'account_journal_entry',
                'permissions' => [
                    // account_journal_entry Permissions
                    'account_journal_entry.create',
                    'account_journal_entry.view',
                    'account_journal_entry.edit',
                    'account_journal_entry.show',
                    'account_journal_entry.delete',
                    'account_journal_entry.validate',
                    'account_journal_entry.confirm',
                    'account_journal_entry.approuve',
                    'account_journal_entry.close',
                    'account_journal_entry.print',
                    'account_journal_entry.reset',
                    'account_journal_entry.reject',
                ]
            ],
            [
                'group_name' => 'account_exercise',
                'permissions' => [
                    // account_exercise Permissions
                    'account_exercise.create',
                    'account_exercise.view',
                    'account_exercise.edit',
                    'account_exercise.show',
                    'account_exercise.delete',
                    'account_exercise.validate',
                    'account_exercise.confirm',
                    'account_exercise.approuve',
                    'account_exercise.close',
                    'account_exercise.print',
                    'account_exercise.reset',
                    'account_exercise.reject',
                ]
            ],

            [
                'group_name' => 'account_ledger',
                'permissions' => [
                    // account_ledger Permissions
                    'account_ledger.create',
                    'account_ledger.view',
                    'account_ledger.edit',
                    'account_ledger.show',
                    'account_ledger.delete',
                    'account_ledger.validate',
                    'account_ledger.confirm',
                    'account_ledger.approuve',
                    'account_ledger.close',
                    'account_ledger.print',
                    'account_ledger.reset',
                    'account_ledger.reject',
                ]
            ],

            [
                'group_name' => 'account_cash_ouflow_request',
                'permissions' => [
                    // account_cash_ouflow_request Permissions
                    'account_cash_ouflow_request.create',
                    'account_cash_ouflow_request.view',
                    'account_cash_ouflow_request.edit',
                    'account_cash_ouflow_request.show',
                    'account_cash_ouflow_request.delete',
                    'account_cash_ouflow_request.validate',
                    'account_cash_ouflow_request.confirm',
                    'account_cash_ouflow_request.approuve',
                    'account_cash_ouflow_request.close',
                    'account_cash_ouflow_request.print',
                    'account_cash_ouflow_request.reset',
                    'account_cash_ouflow_request.reject',
                ]
            ],

            [
                'group_name' => 'account_cash_ouflow',
                'permissions' => [
                    // account_cash_ouflow Permissions
                    'account_cash_ouflow.create',
                    'account_cash_ouflow.view',
                    'account_cash_ouflow.edit',
                    'account_cash_ouflow.show',
                    'account_cash_ouflow.delete',
                    'account_cash_ouflow.validate',
                    'account_cash_ouflow.confirm',
                    'account_cash_ouflow.approuve',
                    'account_cash_ouflow.close',
                    'account_cash_ouflow.print',
                    'account_cash_ouflow.reset',
                    'account_cash_ouflow.reject',
                ]
            ],
            [
                'group_name' => 'fund_transfer',
                'permissions' => [
                    // fund_transfer Permissions
                    'fund_transfer.create',
                    'fund_transfer.view',
                    'fund_transfer.edit',
                    'fund_transfer.show',
                    'fund_transfer.delete',
                    'fund_transfer.validate',
                    'fund_transfer.confirm',
                    'fund_transfer.approuve',
                    'fund_transfer.close',
                    'fund_transfer.print',
                    'fund_transfer.reset',
                    'fund_transfer.reject',
                ]
            ],
            [
                'group_name' => 'advance_payment',
                'permissions' => [
                    // advance_payment Permissions
                    'advance_payment.create',
                    'advance_payment.view',
                    'advance_payment.edit',
                    'advance_payment.show',
                    'advance_payment.delete',
                    'advance_payment.validate',
                    'advance_payment.confirm',
                    'advance_payment.approuve',
                    'advance_payment.close',
                    'advance_payment.print',
                    'advance_payment.reset',
                    'advance_payment.reject',
                ]
            ],
            [
                'group_name' => 'account_expense_category',
                'permissions' => [
                    // account_expense_category Permissions
                    'account_expense_category.create',
                    'account_expense_category.view',
                    'account_expense_category.edit',
                    'account_expense_category.delete',
                ]
            ],
            [
                'group_name' => 'account_expense',
                'permissions' => [
                    // account_expense Permissions
                    'account_expense.create',
                    'account_expense.view',
                    'account_expense.edit',
                    'account_expense.delete',
                ]
            ],
            [
                'group_name' => 'account_cost_center',
                'permissions' => [
                    // account_cost_center Permissions
                    'account_cost_center.create',
                    'account_cost_center.view',
                    'account_cost_center.edit',
                    'account_cost_center.delete',
                ]
            ],
            [
                'group_name' => 'profit_and_loss_statement',
                'permissions' => [
                    // profit_and_loss_statement Permissions
                    'profit_and_loss_statement.create',
                    'profit_and_loss_statement.view',
                ]
            ],
            [
                'group_name' => 'active_balance_sheet',
                'permissions' => [
                    // active_balance_sheet Permissions
                    'active_balance_sheet.create',
                    'active_balance_sheet.view',
                ]
            ],
            [
                'group_name' => 'passive_balance_sheet',
                'permissions' => [
                    // passive_balance_sheet Permissions
                    'passive_balance_sheet.create',
                    'passive_balance_sheet.view',
                ]
            ],
            [
                'group_name' => 'financial_report',
                'permissions' => [
                    // financial_report Permissions
                    'financial_report.create',
                    'financial_report.view',
                ]
            ],
        ];


        // Do same for the admin guard for tutorial purposes
        $roleSuperAdmin = Role::create(['name' => 'superadmin', 'guard_name' => 'admin']);

        // Create and Assign Permissions
        for ($i = 0; $i < count($permissions); $i++) {
            $permissionGroup = $permissions[$i]['group_name'];
            for ($j = 0; $j < count($permissions[$i]['permissions']); $j++) {
                // Create Permission
                $permission = Permission::create(['name' => $permissions[$i]['permissions'][$j], 'group_name' => $permissionGroup, 'guard_name' => 'admin']);
                $roleSuperAdmin->givePermissionTo($permission);
                $permission->assignRole($roleSuperAdmin);
            }
        }

        // Assign super admin role permission to superadmin user
        $admin = Admin::where('username', 'superadmin')->first();
        if ($admin) {
            $admin->assignRole($roleSuperAdmin);
        }
    }
}
