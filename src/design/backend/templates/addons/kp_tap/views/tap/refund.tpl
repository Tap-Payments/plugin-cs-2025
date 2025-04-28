<div id="shipment_wizard">
    <h4 class="subheader">{__("order")}: {$order_id} </h4>
    <form action="{""|fn_url}" method="post" name="tap_form" class="form-horizontal">

        <input type="hidden" name="order_id" value="{$order_id}" />

        <fieldset >
            <div class="control-group">
                <label class="control-label cm-required" for="elm_amount">{__("kp_tap.amount")}</label>
                <div class="controls">
                    <input type="number" name="amount" id="elm_amount" step="0.01" min="1" max="{$amount}" value="{$amount}" />
                </div>
            </div>
        </fieldset>
        <div class="buttons-container">
            <input type="submit" class="btn btn-primary" name="dispatch[tap.refund]" value="{__("kp_tap.refund")}" />
            {include file="addons/kp_tap/components/close_popup.tpl"}
        </div>

    </form>
</div>
