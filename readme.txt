=== Plugin Name ===
Contributors: conductrics, 9seeds
Tags: multivariate, abtest, optimization, learning, experiments, analytics
Requires at least: 3.4
Tested up to: 3.5.1
Stable tag: trunk
License: Creative Commons 3.0
License URI: http://creativecommons.org/licenses/by-sa/3.0/

Adaptive A/B testing with Conductrics -- Auto-Optimize Conversions on your Wordpress Site.

== Description ==

Make it easy to connect your Word Press site to you Conductrics account.

With Conductrics you can run both standard and adaptive AB Tests. Conductrics learns what converts
best and your WordPress site can optimize itself, actively selecting the best option for each user,
in real time, automatically.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `conductrics-api/` folder to the `/wp-content/plugins/` directory
2. Signup for a free account at http://www.conductrics.com
3. Activate the plugin through the 'Plugins' menu in WordPress
4. When prompted, enter the API Key and Owner Code from the sign-up email.

== Frequently Asked Questions ==

= I lost my signup email, where can I find my credentials? =

If you want us to re-send the signup email, just use the signup page at http://www.conductrics.com again with the same email.

If you can access the Conductrics console, you will find your API Keys and Owner code in the
"Keys and Password" tab under the Account dropdown.

= How do I set up a test? =

A test compares and optimizes two or more alternatives for a Page.

So, first, you need at least two pages that you want to compare.

For example, if you want to test your "/signup-page".
First, create your alternative page, e.g. "/signup-page-2".
Open "/signup-page" in the Pages editor, and select "Test this page".
Select "/signup-page-2" as the alternate page.

Once published, any visitors to "/signup-page" will be automatically assigned (by Conductrics) to
see the content of either "/signup-page" or "/signup-page-2"; even though the URL will always appear
to them as "/signup-page".

For the plugin to automatically learn (and optimize it's choices) you should also set a Goal page,
e.g. "/thank-you".

= How many pages can be in one test? =

There must be at least two, and there is no upper limit.  Though you should limit yourself to only a
few at one time so you don't spread your traffic too thinly.

= How can I disable the adaptive learning and just run simple A/B testing? =

1. Use your email and password from the signup email
2. Login to http://console.conductrics.com
3. Select the agent that corresponds to your test
4. Click the "Agent" button (in the upper right)
5. Select either "Adaptive" or "Testing only"

In "Testing only" mode, alternate pages will be given equal preference for the life of the test.
In "Adaptive" mode, alternate pages that perform better will, over time, be chosen more often.

= How do I remove/end a test? =

Select the page you are testing from the Pages view, and uncheck the "Test this page" box.
Once published, the test is no longer running in WordPress.

= If I remove/end a test, is the data gone? =

No, you can always login to http://console.conductrics.com to review (or delete) historical data and reports.

= I created a test, but I don't see a new Agent in the Conductrics Console =

Most likely, there was a typo in the API credentials you entered when the plugin was installed.
Go to the Settings section of your WordPress and select "Conductrics" from the navigation, this will
re-open the Settings dialog, and verify/re-enter your API keys.

= All else has failed, what now? =

Feel free to contact us:
  - Live chat: http://www.conductrics.com (look for "Chat with us!" in the bottom left)
	- Email: info@conductrics.com

== Screenshots ==

== Changelog ==

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.
