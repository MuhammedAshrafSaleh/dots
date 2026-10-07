
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Datasheet / photometric (IES, LDT) / installation file list. files: {name, ext, size, href?, kind?}[] */
export function FileDownloads({ files = [] }) {
  const { tx, t } = useLocale();
  const icon = { pdf: 'file-text', ies: 'lightbulb', ldt: 'lightbulb', dwg: 'pencil-ruler', zip: 'folder-archive' };
  return (
    <div className="d-files">
      {files.map((f, i) => (
        <a key={i} className="d-file" href={f.href || '#'} onClick={(e) => !f.href && e.preventDefault()} download>
          <span className="d-file__ico"><Icon name={icon[f.ext] || 'file'} size={20} /><span className="d-file__ext">{f.ext.toUpperCase()}</span></span>
          <span style={{ minWidth: 0 }}>
            <div className="d-file__name">{tx(f.name)}</div>
            <div className="d-file__meta">{tx(f.kind) || f.ext.toUpperCase()} · {f.size}</div>
          </span>
          <span className="d-file__dl" aria-label={t('Download', 'تحميل')}><Icon name="download" size={16} /></span>
        </a>
      ))}
    </div>
  );
}

