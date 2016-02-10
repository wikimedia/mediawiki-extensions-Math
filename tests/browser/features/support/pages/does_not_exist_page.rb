class DoesNotExistPage
  include PageObject

  page_url '<%=params[:page_name]%>?action=edit'
end
