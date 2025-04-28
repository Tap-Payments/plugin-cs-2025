{if kp_tap_can_refund_order($order_info)}

        <a class="btn cm-dialog-opener cm-ajax"
           href="{"tap.refund?order_id=`$order_info.order_id`"|fn_url}"
           data-ca-dialog-title="Refund"
        >{__("kp_tap.refund")}</a>
{/if}