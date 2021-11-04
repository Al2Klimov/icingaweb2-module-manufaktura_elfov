-- SPDX-License-Identifier: AGPL-3.0-or-later

CREATE TABLE polit_prisoner
(
    id     SMALLSERIAL,
    name   VARCHAR(255) NOT NULL,
    born   DATE,
    source UUID         NOT NULL,

    CONSTRAINT polit_prisoner_pk PRIMARY KEY (id),
    CONSTRAINT polit_prisoner_uk_name UNIQUE (name)
);

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
    value          TEXT NOT NULL,

    CONSTRAINT polit_prisoner_attr_pk PRIMARY KEY (polit_prisoner, field),
    CONSTRAINT polit_prisoner_attr_fk_polit_prisoner FOREIGN KEY (polit_prisoner) REFERENCES polit_prisoner (id),
    CONSTRAINT polit_prisoner_attr_fk_field FOREIGN KEY (field) REFERENCES polit_prisoner_field (id)
);
