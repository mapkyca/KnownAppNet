<div class="row">

    <div class="span10 offset1">
        <h3>App.net</h3>
        <?=$this->draw('account/menu')?>
    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <form action="/account/appnet/" class="form-horizontal" method="post">
            <?php
                if (empty(\Idno\Core\site()->session()->currentUser()->appnet)) {
            ?>
                    <div class="control-group">
                        <div class="controls">
                            <p>
                                If you have a App.net account, you may connect it here. Public content that you
                                post to this site can then be cross-posted to your App.net wall.
                            </p>
                            <p>
                                <a href="<?=$vars['login_url']?>" class="btn btn-large btn-success">Click here to connect App.net to your account</a>
                            </p>
                        </div>
                    </div>
                <?php

                } else {

                    ?>
                    <div class="control-group">
                        <div class="controls">
                            <p>
                                Your account is currently connected to App.net. Public content that you
                                post to this site can then be cross-posted to your App.net wall.
                            </p>
                            <p>
                                <input type="hidden" name="remove" value="1" />
                                <button type="submit" class="btn btn-large btn-primary">Click here to remove App.net from your account.</button>
                            </p>
                        </div>
                    </div>

                <?php

                }
            ?>
            <?= \Idno\Core\site()->actions()->signForm('/account/appnet/')?>
        </form>
    </div>
</div>