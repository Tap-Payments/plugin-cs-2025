<div class="control-group">
   <label class="control-label" for="mo_mode">{__("kp_tap.mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="mo_mode">
            <option value="test" {if $processor_params.mode=='test'}selected="selected"{/if}>Test</option>
            <option value="live" {if $processor_params.mode=='live'}selected="selected"{/if}>Live</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_test_public_key">{__("kp_tap.test_public_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][test_public_key]" id="mo_test_public_key" value="{$processor_params.test_public_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_test_private_key">{__("kp_tap.test_private_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][test_private_key]" id="mo_test_private_key" value="{$processor_params.test_private_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_public_key">{__("kp_tap.public_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][public_key]" id="mo_public_key" value="{$processor_params.public_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_private_key">{__("kp_tap.private_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][private_key]" id="mo_private_key" value="{$processor_params.private_key}" class="input-text" size="60" />
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="mo_merchant_id">Merchant ID:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="mo_merchant_id" value="{$processor_params.merchant_id}" class="input-text" size="60" />
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="uimode">{__("kp_tap.uimode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][uimode]" id="uimode">
            <option value="popup" {if $processor_params.uimode == "popup"}selected="selected"{/if}>{("popup")}</option>
            <option value="redirect" {if $processor_params.uimode == "redirect"}selected="selected"{/if}>{("redirect")}</option>
        </select>
    </div>
</div>



<div class="control-group">
    <label class="control-label" for="payment_mode">{__("kp_tap.payment_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][payment_mode]" id="payment_mode">
            <option value="charge" {if $processor_params.payment_mode == "charge"}selected="selected"{/if}>{("charge")}</option>
            <option value="authorize" {if $processor_params.payment_mode == "authorize"}selected="selected"{/if}>{("authorize")}</option>
        </select>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="language">{__("kp_tap.language")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][language]" id="language">
            <option value="english" {if $processor_params.language == "english"}selected="selected"{/if}>{("english")}</option>
            <option value="arabic" {if $processor_params.language == "arabic"}selected="selected"{/if}>{("arabic")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="save_card">{__("kp_tap.save_card")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][save_card]" id="save_card">
            <option value="no" {if $processor_params.save_card == "no"}selected="selected"{/if}>{("no")}</option>
            <option value="yes" {if $processor_params.save_card == "yes"}selected="selected"{/if}>{("yes")}</option>
        </select>
    </div>
</div>






