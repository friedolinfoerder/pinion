<div id='pinion-page-login'>
    <span id='pinion-page-login-title'>pinion-cms login</span>
    <span id="pinion-page-login-error">
        <?php if($get("isWrong")) print "wrong username or password"; ?>
    </span>
    <form method='post'>
        <div id='pinion-page-login-inputs'>
            <div id='pinion-page-login-inputs-positioning'>
                <label for='username'>Username</label>
                <input type='text' value='<?php $login() ?>' name='username' />
                <label for='password'>Password</label>
                <input type='password' name='password' />
            </div>
        </div>
        <input id='pinion-page-login-submit' type='submit' value='Login' />
    </form>
</div>