#
# This file is subject to the license terms in the LICENSE file found in the
# qa-browsertests top-level directory and at
# https://git.wikimedia.org/blob/qa%2Fbrowsertests/HEAD/LICENSE. No part of
# qa-browsertests, including this file, may be copied, modified, propagated, or
# distributed except according to the terms contained in the LICENSE file.
#
# Copyright 2012-2014 by the Mediawiki developers. See the CREDITS file in the
# qa-browsertests top-level directory and at
# https://git.wikimedia.org/blob/qa%2Fbrowsertests/HEAD/CREDITS
#

Given(/^I am at page that does not exist$/) do
  visit(DoesNotExistPage, using_params: {page_name: @random_string})
end

When(/^I click link Create source$/) do
  on(DoesNotExistPage).create_source_element.when_present.click
end

When(/^I click Preview$/) do
  on(EditPage).preview
end

When(/^I type (.+)$/) do |write_text|
  on(EditPage).article_text=write_text
end

Then(/^alt for that img should be (.+)$/) do |alt|
  on(EditPage).math_image_element.element.alt.should == alt
end

Then(/^src for that img should come from (.+)$/) do |src|
  on(EditPage).math_image_element.element.src.should match Regexp.escape(src)
end


Then(/^the page should contain an img tag$/) do
  on(EditPage).math_image_element.when_present.should be_visible
end