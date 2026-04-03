export function formatRequestBody(body, nonce) {
  var formatted = [];

  // add security nonce
  body.push(
      {name: 'security', value: nonce},
      {name: 'flow', value: 'checkout'},
  );

  jQuery.each(body, function(index, item) {
    formatted.push(item.name + '=' + item.value);
  });

  return formatted.join('&');
}
