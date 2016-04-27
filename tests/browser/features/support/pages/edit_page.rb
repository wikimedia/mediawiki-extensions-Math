class EditPage
  include PageObject

  page_url 'Special:Random?action=edit'

  text_area(:article_text, id: 'wpTextbox1')
  button(:preview, id: 'wpPreview')

  def math_image_element
    if env.lookup(:mediawiki_environment, default: nil) == 'beta'
      browser.meta(class: 'mwe-math-fallback-image-inline')
    else
      browser.img(class: 'tex')
    end
  end
end
