-- SPDX-License-Identifier: AGPL-3.0-or-later

CREATE TABLE polit_prisoner_source
(
    id          UUID,
    last_import TIMESTAMPTZ NOT NULL,

    CONSTRAINT polit_prisoner_source_pk PRIMARY KEY (id)
);

CREATE TABLE polit_prisoner
(
    id         SMALLSERIAL,
    name       VARCHAR(255) NOT NULL,
    born       DATE,
    source     UUID         NOT NULL,
    last_seen  TIMESTAMPTZ  NOT NULL,

    born_month SMALLINT     NOT NULL GENERATED ALWAYS AS ( CASE WHEN born ISNULL THEN 0 ELSE EXTRACT(MONTH FROM born) END ) STORED,
    born_dom   SMALLINT     NOT NULL GENERATED ALWAYS AS ( CASE WHEN born ISNULL THEN 0 ELSE EXTRACT(DAY FROM born) END ) STORED,

    CONSTRAINT polit_prisoner_pk PRIMARY KEY (id),
    CONSTRAINT polit_prisoner_uk_name UNIQUE (name),
    CONSTRAINT polit_prisoner_fk_source FOREIGN KEY (source) REFERENCES polit_prisoner_source (id)
);

CREATE INDEX polit_prisoner_ix_birthday ON polit_prisoner (born_month, born_dom);

CREATE TABLE polit_prisoner_field
(
    id   SMALLSERIAL,
    name VARCHAR(255) NOT NULL,

    CONSTRAINT polit_prisoner_field_pk PRIMARY KEY (id),
    CONSTRAINT polit_prisoner_field_uk_name UNIQUE (name)
);

CREATE TABLE polit_prisoner_attr
(
    polit_prisoner SMALLSERIAL,
    field          SMALLSERIAL,
    value          TEXT        NOT NULL,
    last_seen      TIMESTAMPTZ NOT NULL,

    CONSTRAINT polit_prisoner_attr_pk PRIMARY KEY (polit_prisoner, field),
    CONSTRAINT polit_prisoner_attr_fk_polit_prisoner FOREIGN KEY (polit_prisoner) REFERENCES polit_prisoner (id),
    CONSTRAINT polit_prisoner_attr_fk_field FOREIGN KEY (field) REFERENCES polit_prisoner_field (id)
);
