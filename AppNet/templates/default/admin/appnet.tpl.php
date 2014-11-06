<div class="row">

    <div class="span10 offset1">
        <h1>App.net</h1>
        <?=$this->draw('admin/menu')?>
    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <form action="/admin/appnet/" class="form-horizontal" method="post">
            <div class="control-group">
                <div class="controls">
                    <p>
                        To begin using App.net, <a href="https://account.app.net/developer/apps/" target="_blank">create a new application in
                            the App.net apps portal</a>.</p>
                    <p>
                        Add the following URL to the OAuth2 callback url / redirect URL box <strong><?=\Idno\Core\site()->config()->url?>appnet/callback</strong>.
                    </p>
                    <p>
                        Once you've finished, fill in the details below:
                    </p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">Client ID</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="App Key" class="span4" name="appId" value="<?=htmlspecialchars(\Idno\Core\site()->config()->appnet['appId'])?>" >
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">Client Secret</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="Secret Key" class="span4" name="secret" value="<?=htmlspecialchars(\Idno\Core\site()->config()->appnet['secret'])?>" >
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
            <?= \Idno\Core\site()->actions()->signForm('/admin/appnet/')?>
        </form>
    </div>
</div>