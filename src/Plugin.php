<?php

namespace DL\TicketsReport;

defined('ABSPATH') || exit;

class Plugin
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addReportPage']);
        add_filter('manage_edit-product_columns', [$this, 'addReportColumn']);
        add_action('manage_product_posts_custom_column', [$this, 'renderReportColumn'], 10, 2);
        add_action('admin_head', [$this, 'setReportColumnWidth']);

        //Hooks internos
        add_action('dl_ticket_manager_report_before_search_form', [$this, 'showSearchReportForm']);
        add_action('dl_ticket_manager_report', [$this, 'showReport'], 10, 6);
    }

    /**
     * Añadimos enlace dentro del menú "Tickets"
     * @return void
     * @author Daniel Lucia
     */
    public function addReportPage()
    {
        add_submenu_page(
            'edit.php?post_type=dl-ticket',
            __('Reports', 'dl-ticket-manager-report'),
            __('Reports', 'dl-ticket-manager-report'),
            'manage_options',
            'dl-ticket-manager-report',
            [$this, 'renderReportPage']
        );
    }

    /**
     * Muestra el buscador de eventos por ID
     * @return void
     * @author Daniel Lucia
     */
    public function showSearchReportForm() {
        echo '<div class="wrap"><h1>' . esc_html__('Reports', 'dl-ticket-manager-report') . '</h1>';
            echo '<form method="get">';
                echo '<input type="hidden" name="page" value="dl-ticket-manager-report" />';
                echo '<input type="hidden" name="post_type" value="dl-ticket" />';
                echo '<p><label>' . esc_html__('Event ID:', 'dl-ticket-manager-report') . '</label></p>';
                echo '<p>';
                    echo '<input type="number" name="event_id" value="" min="1" style="width:100px;" /> ';
                    submit_button(__('View report', 'dl-ticket-manager-report'), 'primary', '', false);
                echo '</p>';
            echo '</form>';
        echo '</div>';
    }

    /**
     * Mostramos el informe completo
     * @param mixed $event_id
     * @param mixed $buyers
     * @param mixed $orders
     * @param mixed $tickets_sold
     * @param mixed $tickets
     * @param mixed $total_income
     * @return void
     * @author Daniel Lucia
     */
    public function showReport($event_id, $buyers, $orders, $tickets_sold, $tickets, $total_income) {

        
        $buyers_unique = array_unique($buyers);
        $capacity = intval(get_post_meta($event_id, '_event_capacity', true));
        $conversion_rate = $capacity > 0 ? round(($tickets_sold / $capacity) * 100, 2) : 0;
        $avg_tickets_per_order = count($orders) > 0 ? round($tickets_sold / count($orders), 2) : 0;

        // 2. Datos de validaciones
        $validated = 0;
        $not_validated = 0;
        $validation_history = [];
        foreach ($tickets as $ticket_id) {
            $status = get_post_meta($ticket_id, 'status', true);
            if ($status === 'validated') {
                $validated++;
                $validation_history[] = [
                    'ticket_id' => $ticket_id,
                    'date'      => get_post_meta($ticket_id, 'validation_date', true),
                    'user'      => get_post_meta($ticket_id, 'validated_by', true),
                ];
            } else {
                $not_validated++;
            }
        }

        $multi_buyers = [];
        $buyer_counts = array_count_values($buyers);
        foreach ($buyer_counts as $email => $count) {
            if ($count > 1) {
                $multi_buyers[] = $email;
            }
        }
        
        $first_buyer = !empty($orders) ? reset($orders)->get_billing_email() : '';
        $last_buyer = !empty($orders) ? end($orders)->get_billing_email() : '';

        echo '<h2>' . esc_html__('Sales Data', 'dl-ticket-manager-report') . '</h2>';
            echo '<ul>';
                echo '<li>' . esc_html__('Total tickets sold:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($tickets_sold) . '</strong></li>';
                echo '<li>' . esc_html__('Total revenue:', 'dl-ticket-manager-report') . ' <strong>' . wc_price($total_income) . '</strong></li>';
                echo '<li>' . esc_html__('Conversion rate:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($conversion_rate) . '%</strong></li>';
                echo '<li>' . esc_html__('Average tickets per order:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($avg_tickets_per_order) . '</strong></li>';
            echo '</ul>';

            echo '<h2>' . esc_html__('Validation Data', 'dl-ticket-manager-report') . '</h2>';
            echo '<ul>';
                echo '<li>' . esc_html__('Validated tickets:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($validated) . '</strong></li>';
                echo '<li>' . esc_html__('Not validated tickets:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($not_validated) . '</strong></li>';
            echo '</ul>';

            echo '<h3>' . esc_html__('Validation History', 'dl-ticket-manager-report') . '</h3>';
            if (!empty($validation_history)) {
                echo '
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>' . esc_html__('Ticket ID', 'dl-ticket-manager-report') . '</th>
                            <th>' . esc_html__('Date/Time', 'dl-ticket-manager-report') . '</th>
                            <th>' . esc_html__('User', 'dl-ticket-manager-report') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
                    foreach ($validation_history as $vh) {
                        echo '
                        <tr>
                            <td>' . esc_html($vh['ticket_id']) . '</td>
                            <td>' . esc_html($vh['date']) . '</td>
                            <td>' . esc_html($vh['user']) . '</td>
                        </tr>';
                    }
                    echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>' . esc_html__('No validations recorded.', 'dl-ticket-manager-report') . '</p>';
            }

            echo '<h2>' . esc_html__('User/Buyer Data', 'dl-ticket-manager-report') . '</h2>';
            echo '<ul>';
                echo '<li>' . esc_html__('Number of unique buyers:', 'dl-ticket-manager-report') . ' <strong>' . esc_html(count($buyers_unique)) . '</strong></li>';
                echo '<li>' . esc_html__('Customers who bought more than one ticket:', 'dl-ticket-manager-report') . ' <strong>' . esc_html(count($multi_buyers)) . '</strong></li>';
                echo '<li>' . esc_html__('First buyer:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($first_buyer) . '</strong></li>';
                echo '<li>' . esc_html__('Last buyer:', 'dl-ticket-manager-report') . ' <strong>' . esc_html($last_buyer) . '</strong></li>';
            echo '</ul>';

            // Export emails to CSV
            echo '<h3>' . esc_html__('Buyers\' Emails (CSV)', 'dl-ticket-manager-report') . '</h3>';
            echo '<form method="post"><textarea rows="4" cols="60" readonly>' . esc_html(implode(',', $buyers_unique)) . '</textarea></form>';

            // Localización de compradores
            echo '<h3>' . esc_html__('Buyers\' Location', 'dl-ticket-manager-report') . '</h3>';
            if (!empty($orders)) {
                echo '<table class="widefat"><thead><tr><th>' . esc_html__('Email', 'dl-ticket-manager-report') . '</th><th>' . esc_html__('Ciudad', 'dl-ticket-manager-report') . '</th><th>' . esc_html__('Provincia', 'dl-ticket-manager-report') . '</th><th>' . esc_html__('País', 'dl-ticket-manager-report') . '</th></tr></thead><tbody>';
                foreach ($orders as $order) {
                    echo '
                    <tr>
                        <td>' . esc_html($order->get_billing_email()) . '</td>
                        <td>' . esc_html($order->get_billing_city()) . '</td>
                        <td>' . esc_html($order->get_billing_state()) . '</td>
                        <td>' . esc_html($order->get_billing_country()) . '</td>
                    </tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>' . esc_html__('There are no registered buyers.', 'dl-ticket-manager-report') . '</p>';
            }
    }

    /**
     * Mostramos página
     * @return void
     * @author Daniel Lucia
     */
    public function renderReportPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'dl-ticket-manager-report'));
        }

        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        if (!$event_id) {
            
            do_action('dl_ticket_manager_report_before_search_form');
            return;

        }

        //Obtenemos todos los datos posibles
        $tickets = get_posts([
            'post_type'      => 'dl-ticket',
            'meta_key'       => 'product_id',
            'meta_value'     => $event_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $total_tickets = count($tickets);

        $orders = [];
        $tickets_sold = 0;
        $total_income = 0;
        $buyers = [];
        foreach ($tickets as $ticket_id) {
            $order_id = get_post_meta($ticket_id, 'order_id', true);
            $order = wc_get_order($order_id);
            if ($order && $order->get_status() === 'completed') {
                $orders[$order_id] = $order;
                $total_income += $order->get_total();
                $tickets_sold++;
                $buyers[] = $order->get_billing_email();
            }
        }
        
        echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Tickets report', 'dl-ticket-manager-report') . '</h1>';
            do_action('dl_ticket_manager_report', $event_id, $buyers, $orders, $tickets_sold, $tickets, $total_income);
        echo '</div>';
    }

    /**
     * Añadimos columna "Ver informe" al listado de productos en el admin
     * @param mixed $columns
     * @author Daniel Lucia
     */
    public function addReportColumn($columns)
    {
        $columns['view-report'] = __('View report', 'dl-ticket-manager-report');
        return $columns;
    }

    /**
     * Mostramos enlace en la columna "Ver informe" solo para productos tipo ticket
     * @param mixed $column
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function renderReportColumn($column, $post_id)
    {
        if ($column === 'view-report') {
            $product = wc_get_product($post_id);
            if ($product && $product->get_type() === 'ticket') {
                $url = admin_url('edit.php?post_type=dl-ticket&page=dl-ticket-manager-report&event_id=' . $post_id);
                echo '<a href="' . esc_url($url) . '">' . esc_html__('View report', 'dl-ticket-manager-report') . '</a>';
            }
        }
    }

    /**
     * Establecemos el ancho de la columna
     * @return void
     * @author Daniel Lucia
     */
    public function setReportColumnWidth()
    {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-product') {
            echo '<style>
                .wp-list-table th.column-view-report { width: 92px; }
            </style>';
        }
    }
}
