--  todo caches
--      member.{ID}.last_active, member.{ID}.stat.codes, system.stat.codes, tag.{ID}.codes
--  todo crons:
--      auto truncate and reset max_id of temp table ( truncate and select setval('y_id_seq',1,false) )

--  用户
create table member (
    id          serial not null primary key,
    username    varchar(64),
    password    varchar(32),
    email       varchar(64),
    email_hash  varchar(32), -- md5(strtolower(trim($email))), cache string
    role        varchar(32),
--  bio         varchar(140),
    date_join   timestamp without time zone,
--  last_active timestamp without time zone, -- todo to be cached 
    blocked     boolean default false
);
create index member_index on member(username, password);

-- 代码
create table code (
    id          serial not null primary key,
    title       varchar(255),
    member_id   integer references member(id) on delete cascade,
    description text,
    markdowned  text,
    code        text,
    codebytes   integer,
    highlighted text,
    tag_ids     integer[],
    language_id integer default 0, -- 0 means 'uncategoriesed'
    created     timestamp without time zone,
    updated     timestamp without time zone
);
create index code_index on code(member_id, tag_ids, language_id);

-- 标签
create table tag (
    id          serial not null primary key,
    tag_name    varchar(255),
    counter     integer default 1,
    member_id   integer references member(id) on delete cascade
);
create index tag_index on tag(tag_name, counter, member_id);

-- 日志 (see ICE_Audit)
create table audit (
    id          serial not null primary key,
    member_id   integer references member(id) on delete cascade,
    content     varchar(255),
    date_audit  timestamp without time zone,
    audit_type  smallint
);
create index audit_index on audit(member_id, audit_type);

-- 记事本
create table note (
    id          serial not null primary key,
    member_id   integer references member(id) on delete cascade,
    title       varchar(255),
    content     text,
    markdowned  text,
    checked     boolean default false,
    tag_ids     integer[],
    top         integer default 0,
    created     timestamp without time zone
);
create index note_index on note(member_id, checked, title, top);

-- note 标签
create table note_tag (
    id          serial not null primary key,
    tag_name    varchar(255),
    counter     integer default 1,
    member_id   integer references member(id) on delete cascade
);
create index note_tag_index on note_tag(tag_name, counter, member_id);

-- init data
insert into member(username,password,email,email_hash,role) values('SYSTEM','','iyanchuan@gmail.com',md5('iyanchuan@gmail.com'),'guest');
insert into member(username,password,email,email_hash,role) values('yc',md5('yanchuan'),'iyanchuan@gmail.com',md5('iyanchuan@gmail.com'),'admin');
-- for fixing problem in join query, see Code model 
insert into tag(tag_name,counter,member_id) values(',RESERVED,', 0, 1);
insert into note_tag(tag_name,counter,member_id) values(',RESERVED,', 0, 1);
