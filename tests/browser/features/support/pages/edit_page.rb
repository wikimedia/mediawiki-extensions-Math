class EditPage
  include PageObject

  page_url 'Special:Random?action=edit'

  text_area(:article_text, id: 'wpTextbox1')
  img(:math_image, class: 'tex')
  button(:preview, id: 'wpPreview')
end
