Given(/^I am editing a random page with source editor$/) do
  visit EditPage
end

When(/^I click Preview$/) do
  on(EditPage).preview
end

When(/^I type (.+)$/) do |write_text|
  on(EditPage).article_text = write_text
end

Then(/^the page should contain 3 \+ 2 image$/) do
  expect(on(EditPage).math_image_element.when_present).to be_visible
end
