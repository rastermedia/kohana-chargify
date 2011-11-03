## Creating a Coupon

Creating new coupons is farily straightfoward:

    $coupon = Chargify::factory('coupon')
        ->values(array(
            'name'              => '100% off forever',
            'code'              => '100forever',
            'description'       => 'This will be displayed to the customers after the coupon validation.',
            'percentage'        => 100,
            'recurring'         => true,
            'product_family_id' => 123,
        ))
        ->save();

Obviously, if you want to make any money, you'll want to specify a
`coupon_end_date` and a `coupon_duration_period_count` ... and maybe a lower
percentage.

**If no `product_family_id` is specified, the first product family available
is used.**

## Retrieving a Coupon

    // Find by coupon ID
    $coupon = Chargify::factory('coupon')->find(321);

    // Find by coupon code
    $coupon = Chargify::factory('coupon')->find_by_code('50forever');

    // Find everything
    $coupon = Chargify::factory('coupon')->find_all();

## Updating a Coupon

Maybe 100% off a subscription _forever_ isn't such a great idea anymore. You
can retrieve the coupon and change its attributes like so:

    $coupon->name = '50% off for now';
    $coupon->code = '50fornow';
    $coupon->percentage = 50;
    $coupon->recurring = false;
    $coupon->coupon_duration_period_count = 2;
    $coupon->save();

Problem solved!

## Validating a Coupon

Time to put the trustworthiness of your customers to the test
with validation. In order to ensure provided coupon codes are correct, we
have to make a request to Chargify:

    if (Chargify::factory('coupon')->validate($_POST['coupon_code']))
    {
        echo 'Okay, okay. The coupon is real.';
    }
    else
    {
        echo 'You sneaky, sneakerson. This coupon is not valid.';
    }

## Archiving a Coupon

So finally, it's time to retire a coupon. The 100% off campaign wasn't as
profitable as we thought and somehow resulted in a 100% loss. While we can
still afford to run the servers, we make a request to archive (disable) the
coupon:

    $coupon->destroy();

And that's all there is to it.