import { addFilter } from '@wordpress/hooks';
import './index.scss';
import { __ } from '@wordpress/i18n';

const addRoleFilters = ( filters ) => {
	return [
		{
			label: __( 'Role', 'woocommerce-admin-filter-by-role' ),
			staticParams: [],
			param: 'role',
			showFilters: () => true,
			defaultValue: '*',
			filters: [ ...( wcSettings.roles || [] ) ],
		},
		...filters,
	];
};

addFilter(
	'woocommerce_admin_orders_report_filters',
	'woocommerce-admin-filter-by-role',
	addRoleFilters
);
