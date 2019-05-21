<?php

/*
  Template Name: Donation Page
 */
?>
<?php get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>

        <div class="entry">
            <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">



                <div class="post-content">
				<?php the_content(); ?>
				<h2>You may give a one-time donation or setup a recurring monthly donation. We use the PayPal.com giving tools, so <enm>your donations are always safe and secure.</em></h2>
<p class="text"><strong>Option 1: One-time Donation</strong></p>

<form class="input2a clearfix" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_donations" />
<input type="hidden" name="business" value="mockingbirdnyc@gmail.com" />
<input type="hidden" name="item_name" value="One-Time Donation" />
<input type="hidden" name="item_number" value="2018" />
<div><span class="inputtext">$</span><input class="input3" type="text" name="amount" value="" size="10"/> <span class="text">Please enter an amount to be charged/debited from your account one time.</span></div>
<br />
<input type="hidden" name="no_shipping" value="2" />
<input type="hidden" name="no_note" value="1" />
<input type="hidden" name="currency_code" value="USD" />
<input type="hidden" name="tax" value="0" />
<input type="hidden" name="bn" value="Donate" />
<input class="input2a" type="image" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
</form>

<hr noshade="noshade" size="1" class="clearfix" />
<p class="text"><strong>Option 2: Setup Recurring Monthly Donation.</strong> All monthly donors to Mockingbird receive a complimentary subscription to our quarterly magazine, The Mockingbird.</p>

<form class="input2 clearfix" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_xclick-subscriptions" />
<input type="hidden" name="business" value="mockingbirdnyc@gmail.com" />
<input type="hidden" name="item_name" value="Recurring Donation" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="no_note" value="1" />
<input type="hidden" name="currency_code" value="USD" />
<input type="hidden" name="lc" value="US" />
<input type="hidden" name="bn" value="Subscribe" />
<div><span class="inputtext">$</span><input class="input3" type="text" name="a3" id="a3" value="" size="10" /> <span class="text">Please enter recurring amount to be charged/debited from your credit or debit card each month <span id="flash">(Minimum of $10)</span>.</span> </div>
<br />
<input type="hidden" name="p3" value="1" />
<input type="hidden" name="t3" value="M" />
<input type="hidden" name="src" value="1" />
<input type="hidden" name="sra" value="1" />
<input class="input2" type="image" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_subscribeCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
<img src="https://www.paypal.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" />
</form>
<script>
function validate_form () {
	var valid = true;
	if ( jQuery("#a3").val() < 10 ){
		alert ( "Donation minimum is $10 a month" );
		valid = false;
	}
	return valid;
}

jQuery(".input2").submit(validate_form);
</script>

	<h2 class="text">Frequently Asked Questions</h2>
<p class="text"><strong>Q1. How do I change how I pay for a recurring donation that I previously created?</strong></p>
<p class="text">To change your method of payment, you will need to log into Paypal by following these steps: "<a class="text" href="https://www.paypal.com/helpcenter/main.jsp;jsessionid=GBLhMSthXVhhFW2RtydWT1Th1MQ925vTh276WYbzjrg8773W4ctq!1277630524?locale=en_US&amp;_dyncharset=UTF-8&amp;countrycode=US&amp;cmd=_help&amp;serverInstance=9016&amp;t=solutionTab&amp;ft=searchTab&amp;ps=solutionPanels&amp;solutionId=27689&amp;isSrch=Yes" target="_blank">PayPal: How do I change the way I pay for a recurring payment, subscription, automatic billing, or installment plan?</a>"</p>
<p class="text"><strong>Q2. How do I change the amount of a recurring donation?</strong></p>
<p class="text">To change your recurring donation amount just enter a new amount and choose the 'Subscribe' button. On the following screen, log into Paypal. Paypal will then prompt you with your old subscription amount and your new amount. Choose 'Save' if the change looks correct.</p>
<p class="text"><strong>Q3. How do I cancel a recurring donation that I previously created?</strong></p>
<p class="text">To cancel your recurring payment, you must log into PayPal and modify your account by following these steps: "<a class="text" href="https://www.paypal.com/helpcenter/main.jsp?cmd=_help&amp;solutionId=27715&amp;t=solutionTab&amp;bn_r=o" target="_blank">PayPal: How do I cancel a recurring payment, subscription, or automatic billing agreement I have with a merchant?</a>"</p>
<p class="text"><strong>Q4. Can I set the day of the month that my recurring donation will come out of my account?</strong></p>
<p class="text">Currently, the ability to set the date for the donation does not exist. The day of the month will be the same as the day you set up the donation subscription. So, if you set up the recurring subscription donation on the 5th of September, the next donation will be the 5th of October and so on.</p>
<strong>Q5. Can I give by check instead of a credit or debit card?</strong>
<p class="text">To make a donation by check, please follow these directions, using mockingbirdnyc@gmail.com as the person or organization to receive your gift: "<a class="text" href="https://www.paypal.com/helpcenter/main.jsp;jsessionid=Q2YyMSyVdB9YsGtmSpymT3QqPTn9jth4cvF4XCkbjk4hg0mFVC0b!-1921082881?locale=en_US&amp;_dyncharset=UTF-8&amp;countrycode=US&amp;cmd=_help&amp;serverInstance=9022&amp;t=solutionTab&amp;ft=searchTab&amp;ps=solutionPanels&amp;solutionId=12233&amp;isSrch=Yes" target="_blank">PayPal: How do I use eCheck?</a>"</p>
<p class="text"><strong>Q6. Is the giving site and PayPal secure?</strong></p>
<p class="text">Yes. We use SSL HTTPS secure pages and PayPal guarantees the security of their website.</p>
<p class="text"><strong>Q7. Can Mockingbord change my recurring or single donation amounts for me?</strong></p>
<p class="text">No. Please make all changes through the PayPal interface. Mockingbird is unable to change individual PayPal accounts. To contact PayPal please visit their <a class="text" href="https://www.paypal.com/us/cgi-bin/helpscr?cmd=_help&amp;t=escalateTab" target="_blank">Contact Us</a> website.</p>
				</div>

			</div>
        </div>

        <?php endwhile; ?>
    <?php endif; ?>



<?php get_footer(); ?>
