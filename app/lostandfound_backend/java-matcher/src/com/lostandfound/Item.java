package com.lostandfound;

public abstract class Item {
    private final int id;
    private final String title;
    private final String description;
    private final String category;
    private final String location;
    private final String status;

    public Item(int id, String title, String description, String category, String location, String status) {
        this.id = id;
        this.title = title == null ? "" : title;
        this.description = description == null ? "" : description;
        this.category = category == null ? "" : category;
        this.location = location == null ? "" : location;
        this.status = status == null ? "" : status;
    }

    public int getId() { return id; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getCategory() { return category; }
    public String getLocation() { return location; }
    public String getStatus() { return status; }
}
