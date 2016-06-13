class EditPage
  include PageObject

  page_url 'Special:Random?action=edit'

  text_area(:article_text, id: 'wpTextbox1')
  button(:preview, id: 'wpPreview')
  span(:start_editing, text: 'Start editing')

  def math_image_element
    browser.img(class: 'mwe-math-fallback-image-inline')
  end
end
