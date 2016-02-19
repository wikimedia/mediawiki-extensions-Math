Given(/^I am editing a random page with source editor$/) do
  visit EditPage
end

When(/^I click Preview$/) do
  on(EditPage).preview
end

When(/^I type (.+)$/) do |write_text|
  on(EditPage).article_text = write_text
end

Then(/^alt for that img should be (.+)$/) do |alt|
  expect(on(EditPage).math_image_element.element.alt).to eq(alt)
end

Then(/^src for that img should contain (.+)$/) do |src|
  expect(on(EditPage).math_image_element.element.src).to match Regexp.escape src
end

Then(/^the page should contain an img tag$/) do
  expect(on(EditPage).math_image_element.when_present).to be_visible
end
