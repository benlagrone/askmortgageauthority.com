import parse from 'html-react-parser';

export default function Content({isPending, pendingContent, content}) {
  return parse( isPending ? pendingContent : content );
}