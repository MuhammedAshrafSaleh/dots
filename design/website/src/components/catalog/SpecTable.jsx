
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';

/** Technical specification table, zebra rows, grouped. groups: {title, rows: [label, value][]}[] */
export function SpecTable({ groups = [], caption }) {
  const { tx } = useLocale();
  return (
    <table className="d-spec">
      {caption && <caption>{tx(caption)}</caption>}
      <tbody>
        {groups.map((g, gi) => (
          <React.Fragment key={gi}>
            {g.title && <tr className="d-spec__group"><th colSpan={2}>{tx(g.title)}</th></tr>}
            {g.rows.map(([k, val], i) => <tr key={i}><th scope="row">{tx(k)}</th><td>{tx(val)}</td></tr>)}
          </React.Fragment>
        ))}
      </tbody>
    </table>
  );
}

